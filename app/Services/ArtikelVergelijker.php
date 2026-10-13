<?php

namespace App\Services;

use App\Categorie;
use App\Models\Gebeurtenis;
use App\Models\Samenvatting;

class ArtikelVergelijker
{
    private int $minimaleArtikelen = 2;

    public function artikelenTekst(Gebeurtenis $gebeurtenis): string
    {
        $gebeurtenis->load('artikelen.bron');

        $regels = [];

        foreach ($gebeurtenis->artikelen as $artikel) {
            $tekst = trim(strip_tags((string) $artikel->volledige_tekst));

            $regels[] = "Bron: {$artikel->bron->naam} \n" // ({$artikel->bron->orientatie})
                ."Titel: {$artikel->titel}\n"
                ."Tekst: {$tekst}";
        }

        return implode("\n\n", $regels);
    }

    public function samenvat(Gebeurtenis $gebeurtenis): array
    {

        // Maak het systeem bericht
        $systeem = "Hieronder volgen nieuwsartikelen over dezelfde gebeurtenis.\n\n"
            ."Schrijf in het Nederlands. Kies zelf geen partij.\n\n"
            .'kernfeiten: één zelfstandig leesbaar stuk. Begin met waar de gebeurtenis over gaat. '
            .'Daarna alleen de feiten die een lezer nodig heeft om het te begrijpen, zonder de artikelen te openen. '
            ."Geen bronnamen, geen analyse van toon.\n\n"
            ."betrokkenen: alleen namen en rollen, gescheiden door komma's. "
            ."Geen zinnen. Bijvoorbeeld: ICE-agent, man (niet bij naam genoemd), vijfjarige zoon, DHS, burgemeester Zohran Mamdani.\n\n"
            .'overeenstemming: analyseer de schrijfwijze, niet de gedeelde feiten. '
            ."Waar lijken toon, woordkeuze en nadruk op elkaar, ook over politieke verschillen heen? Noem bronnen bij naam.\n\n"
            .'verschil: analyseer hoe woordkeuze, nadruk en weglatingen de gebeurtenis kleuren. '
            .'Koppel dat aan hoe bronnen schrijven (bijvoorbeeld beladen termen, wie als dader of slachtoffer wordt neergezet, welke details extra aandacht krijgen). '
            ."Noem bronnen bij naam. Gebruik geen labels als left, right of neutral.\n\n"
            ."categorie: kies precies één categorie die het best past:\n"
            .Categorie::promptUitleg();

        // Haal de artikelen tekst op
        $artikelen = $this->artikelenTekst($gebeurtenis);

        // Vraag de AI om een samenvatting te genereren met een JSON schema
        $antwoord = (new LiteLlm)->vraag($systeem, $artikelen, [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'samenvatting',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'kernfeiten' => ['type' => 'string'],
                        'betrokkenen' => ['type' => 'string'],
                        'overeenstemming' => ['type' => 'string'],
                        'verschil' => ['type' => 'string'],
                        'categorie' => ['type' => 'string', 'enum' => Categorie::waarden()],
                    ],
                    'required' => ['kernfeiten', 'betrokkenen', 'overeenstemming', 'verschil', 'categorie'],
                    'additionalProperties' => false,
                ],
            ],
        ], $gebeurtenis->id);

        // Controleer of het antwoord een geldige JSON is
        $data = json_decode($antwoord, true);

        // Geef het antwoord terug
        return is_array($data) ? $data : [];
    }

    /**
     * Bepaalt alleen de categorie, voor gebeurtenissen die al een samenvatting hebben of er nog geen krijgen.
     */
    public function categoriseer(Gebeurtenis $gebeurtenis): Categorie
    {
        $systeem = "Hieronder volgen nieuwsartikelen over dezelfde gebeurtenis.\n\n"
            ."Kies precies één categorie die het best past:\n"
            .Categorie::promptUitleg();

        $antwoord = (new LiteLlm)->vraag($systeem, $this->artikelenTekst($gebeurtenis), [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'categorie',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categorie' => ['type' => 'string', 'enum' => Categorie::waarden()],
                    ],
                    'required' => ['categorie'],
                    'additionalProperties' => false,
                ],
            ],
        ], $gebeurtenis->id);

        $data = json_decode($antwoord, true);
        $categorie = Categorie::tryFrom(is_array($data) && is_string($data['categorie'] ?? null) ? $data['categorie'] : '');

        if ($categorie === null) {
            throw new \RuntimeException('Ongeldige JSON van het model: veld categorie ontbreekt of is geen bekende categorie.');
        }

        $gebeurtenis->update(['categorie' => $categorie]);

        return $categorie;
    }

    public function slaOp(Gebeurtenis $gebeurtenis): ?Samenvatting
    {
        $samenvatting = $gebeurtenis->samenvatting;

        $aantal = $gebeurtenis->artikelen()->count();

        // Stop als er te weinig artikelen zijn
        if ($aantal < $this->minimaleArtikelen) {
            return $samenvatting;
        }

        // Stop als er geen nieuwe artikelen zijn
        if ($samenvatting && ! $this->heeftNieuweArtikelen($gebeurtenis, $samenvatting)) {
            return $samenvatting;
        }

        // Genereer de samenvatting
        $data = $this->samenvat($gebeurtenis);

        // Controleer of de JSON geldig is
        // Stop als er een veld ontbreekt of het geen tekstveld is
        foreach (['kernfeiten', 'betrokkenen', 'overeenstemming', 'verschil'] as $veld) {
            if (! array_key_exists($veld, $data) || ! is_string($data[$veld]) || trim($data[$veld]) === '') {
                throw new \RuntimeException("Ongeldige JSON van het model: veld {$veld} ontbreekt of is geen tekstveld.");
            }
        }

        // De categorie hoort bij de gebeurtenis, niet bij de samenvatting
        $categorie = Categorie::tryFrom(is_string($data['categorie'] ?? null) ? $data['categorie'] : '');

        if ($categorie === null) {
            throw new \RuntimeException('Ongeldige JSON van het model: veld categorie ontbreekt of is geen bekende categorie.');
        }

        $gebeurtenis->update(['categorie' => $categorie]);
        unset($data['categorie']);

        // Voeg de gebeurtenis_id en het gebruikte model toe aan de data
        $data['gebeurtenis_id'] = $gebeurtenis->id;
        $data['model'] = config('services.litellm.model');

        // Update of create de samenvatting
        if ($samenvatting) {
            $samenvatting->update($data);

            return $samenvatting;
        }

        return Samenvatting::create($data);
    }

    // Check of er nieuwe artikelen zijn
    private function heeftNieuweArtikelen(Gebeurtenis $gebeurtenis, Samenvatting $samenvatting): bool
    {
        $nieuwste = $gebeurtenis->artikelen()->max('created_at'); // Nieuwste artikel

        return $nieuwste > $samenvatting->updated_at; // Nieuwste artikel is later dan de samenvatting
    }
}
