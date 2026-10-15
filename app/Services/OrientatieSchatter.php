<?php

namespace App\Services;

use App\Models\Artikel;

/**
 * Laat de AI de politieke oriëntatie van één artikel schatten, als tweede meting naast het MBFC-label van de bron.
 * De bronnaam en het MBFC-label gaan bewust niet mee in de prompt, anders neemt het model het label over.
 */
class OrientatieSchatter
{
    /**
     * Dezelfde labels als MBFC, zodat de schatting direct te vergelijken is met bronnen.orientatie.
     */
    public const LABELS = ['left', 'left-center', 'neutral', 'right-center', 'right'];

    /**
     * Onder deze tekstlengte (of zonder politieke lading) zijn er te weinig aanwijzingen voor een zekere schatting.
     */
    private const MINIMALE_TEKSTLENGTE = 300;

    private const MAXIMALE_ZEKERHEID_BIJ_WEINIG_AANWIJZINGEN = 40;

    /**
     * @return array{politiek_geladen: bool, orientatie: string, zekerheid: int, onderbouwing: string}
     */
    public function schat(Artikel $artikel): array
    {
        $systeem = 'Hieronder volgt één nieuwsartikel. Schat de politieke oriëntatie van dit artikel '
            .'op de schaal van Media Bias/Fact Check: '.implode(', ', self::LABELS).".\n\n"
            .'politiek_geladen: true als het artikel over een politiek of maatschappelijk omstreden onderwerp gaat '
            .'waarover links en rechts verschillend denken (bijvoorbeeld migratie, oorlog, klimaat, economisch beleid, verkiezingen). '
            .'false bij sport, cultuur, bedrijfsnieuws, weer of ander nieuws zonder politieke lading. '
            ."Kies bij false orientatie neutral.\n\n"
            .'orientatie: beoordeel hoe het artikel geschreven is, niet het onderwerp: woordkeuze, beladen termen, '
            .'wie als dader of slachtoffer wordt neergezet, welke standpunten ruimte krijgen en wat wordt weggelaten. '
            .'Een artikel over een links of rechts onderwerp is niet vanzelf links of rechts. '
            ."Zakelijke berichtgeving over een politiek onderwerp zonder duidelijke kleuring is neutral.\n\n"
            .'zekerheid: een geheel getal van 0 tot 100: hoe zeker ben je over de richting van de oriëntatie. '
            .'Dit gaat niet over hoe zeker je bent dat er weinig kleuring is. '
            .'Geef hooguit '.self::MAXIMALE_ZEKERHEID_BIJ_WEINIG_AANWIJZINGEN.' als politiek_geladen false is '
            .'of als de tekst korter is dan ongeveer '.self::MINIMALE_TEKSTLENGTE.' tekens, '
            ."omdat er dan te weinig aanwijzingen zijn.\n\n"
            .'onderbouwing: één of twee zinnen in het Nederlands met de concrete aanwijzingen uit de tekst.';

        $tekst = trim(strip_tags((string) $artikel->volledige_tekst));
        $bericht = "Titel: {$artikel->titel}\nTekst: {$tekst}";

        $antwoord = (new LiteLlm)->vraag($systeem, $bericht, [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'orientatie',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'politiek_geladen' => ['type' => 'boolean'],
                        'orientatie' => ['type' => 'string', 'enum' => self::LABELS],
                        'zekerheid' => ['type' => 'integer'],
                        'onderbouwing' => ['type' => 'string'],
                    ],
                    'required' => ['politiek_geladen', 'orientatie', 'zekerheid', 'onderbouwing'],
                    'additionalProperties' => false,
                ],
            ],
        ], $artikel->gebeurtenis_id, 'orientatie');

        $data = json_decode($antwoord, true);

        if (! is_array($data) || ! is_bool($data['politiek_geladen'] ?? null)) {
            throw new \RuntimeException('Ongeldige JSON van het model: veld politiek_geladen ontbreekt of is geen true/false.');
        }

        if (! in_array($data['orientatie'] ?? null, self::LABELS, true)) {
            throw new \RuntimeException('Ongeldige JSON van het model: veld orientatie ontbreekt of is geen bekend label.');
        }

        if (! is_int($data['zekerheid'] ?? null) || $data['zekerheid'] < 0 || $data['zekerheid'] > 100) {
            throw new \RuntimeException('Ongeldige JSON van het model: veld zekerheid is geen geheel getal van 0 tot 100.');
        }

        if (! is_string($data['onderbouwing'] ?? null) || trim($data['onderbouwing']) === '') {
            throw new \RuntimeException('Ongeldige JSON van het model: veld onderbouwing ontbreekt of is leeg.');
        }

        // Het model houdt zich niet altijd aan de bovengrens uit de prompt, dus die wordt hier afgedwongen
        $weinigAanwijzingen = ! $data['politiek_geladen'] || mb_strlen($tekst) < self::MINIMALE_TEKSTLENGTE;

        return [
            'politiek_geladen' => $data['politiek_geladen'],
            'orientatie' => $data['politiek_geladen'] ? $data['orientatie'] : 'neutral',
            'zekerheid' => $weinigAanwijzingen ? min($data['zekerheid'], self::MAXIMALE_ZEKERHEID_BIJ_WEINIG_AANWIJZINGEN) : $data['zekerheid'],
            'onderbouwing' => trim($data['onderbouwing']),
        ];
    }

    public function slaOp(Artikel $artikel): Artikel
    {
        $schatting = $this->schat($artikel);

        $artikel->update([
            'ai_orientatie' => $schatting['orientatie'],
            'ai_politiek' => $schatting['politiek_geladen'],
            'ai_zekerheid' => $schatting['zekerheid'],
            'ai_onderbouwing' => $schatting['onderbouwing'],
            'ai_model' => config('services.litellm.model'),
            'ai_geschat_op' => now(),
        ]);

        return $artikel;
    }
}
