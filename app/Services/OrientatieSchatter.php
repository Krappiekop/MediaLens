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
     * @return array{orientatie: string, zekerheid: int, onderbouwing: string}
     */
    public function schat(Artikel $artikel): array
    {
        $systeem = 'Hieronder volgt één nieuwsartikel. Schat de politieke oriëntatie van dit artikel '
            .'op de schaal van Media Bias/Fact Check: '.implode(', ', self::LABELS).".\n\n"
            .'Beoordeel hoe het artikel geschreven is, niet het onderwerp: woordkeuze, beladen termen, '
            .'wie als dader of slachtoffer wordt neergezet, welke standpunten ruimte krijgen en wat wordt weggelaten. '
            .'Een artikel over een links of rechts onderwerp is niet vanzelf links of rechts. '
            ."Zakelijke berichtgeving zonder duidelijke kleuring is neutral.\n\n"
            .'zekerheid: een geheel getal van 0 tot 100. Geef een lage zekerheid als de tekst kort is '
            ."of weinig aanwijzingen bevat.\n\n"
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
                        'orientatie' => ['type' => 'string', 'enum' => self::LABELS],
                        'zekerheid' => ['type' => 'integer'],
                        'onderbouwing' => ['type' => 'string'],
                    ],
                    'required' => ['orientatie', 'zekerheid', 'onderbouwing'],
                    'additionalProperties' => false,
                ],
            ],
        ], $artikel->gebeurtenis_id);

        $data = json_decode($antwoord, true);

        if (! is_array($data) || ! in_array($data['orientatie'] ?? null, self::LABELS, true)) {
            throw new \RuntimeException('Ongeldige JSON van het model: veld orientatie ontbreekt of is geen bekend label.');
        }

        if (! is_int($data['zekerheid'] ?? null) || $data['zekerheid'] < 0 || $data['zekerheid'] > 100) {
            throw new \RuntimeException('Ongeldige JSON van het model: veld zekerheid is geen geheel getal van 0 tot 100.');
        }

        if (! is_string($data['onderbouwing'] ?? null) || trim($data['onderbouwing']) === '') {
            throw new \RuntimeException('Ongeldige JSON van het model: veld onderbouwing ontbreekt of is leeg.');
        }

        return [
            'orientatie' => $data['orientatie'],
            'zekerheid' => $data['zekerheid'],
            'onderbouwing' => trim($data['onderbouwing']),
        ];
    }

    public function slaOp(Artikel $artikel): Artikel
    {
        $schatting = $this->schat($artikel);

        $artikel->update([
            'ai_orientatie' => $schatting['orientatie'],
            'ai_zekerheid' => $schatting['zekerheid'],
            'ai_onderbouwing' => $schatting['onderbouwing'],
            'ai_model' => config('services.litellm.model'),
            'ai_geschat_op' => now(),
        ]);

        return $artikel;
    }
}
