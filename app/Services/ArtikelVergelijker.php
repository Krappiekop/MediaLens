<?php

namespace App\Services;

use App\Models\Gebeurtenis;
use App\Services\LiteLlm;
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

            $regels[] = "Bron: {$artikel->bron->naam} \n" //({$artikel->bron->orientatie})
                . "Titel: {$artikel->titel}\n"
                . "Tekst: {$tekst}";
        }

        return implode("\n\n", $regels);
    }

    // prompt voor de ai om artikelen binnen een gebeurtenis te vergelijken
    // public function vergelijk(Gebeurtenis $gebeurtenis): string
    // {
    //     $artikelen = $this->artikelenTekst($gebeurtenis);

    //     $prompt = "Hieronder staan nieuwsartikelen over dezelfde gebeurtenis, uit bronnen met verschillende politieke oriëntatie.\n\n"
    //         . "Vergelijk de artikelen. Benoem:\n"
    //         . "- taalgebruik (neutraal, emotioneel, beladen woorden)\n"
    //         . "- welke onderwerpen extra worden benadrukt\n"
    //         . "- welke actoren (personen, instanties) worden genoemd of weggelaten\n\n"
    //         . "Artikelen:\n\n"
    //         . $artikelen;

    //     return (new LiteLlm)->vraag($prompt, null, null, $gebeurtenis->id);
    // }

    public function samenvat(Gebeurtenis $gebeurtenis): array
    {


        // Maak het systeem bericht
        $systeem = "Hieronder volgen nieuwsartikelen over dezelfde gebeurtenis.\n\n"
            . "Schrijf in het Nederlands. Kies zelf geen partij.\n\n"
            . "kernfeiten: één zelfstandig leesbaar stuk. Begin met waar de gebeurtenis over gaat. "
            . "Daarna alleen de feiten die een lezer nodig heeft om het te begrijpen, zonder de artikelen te openen. "
            . "Geen bronnamen, geen analyse van toon.\n\n"
            . "betrokkenen: alleen namen en rollen, gescheiden door komma's. "
            . "Geen zinnen. Bijvoorbeeld: ICE-agent, man (niet bij naam genoemd), vijfjarige zoon, DHS, burgemeester Zohran Mamdani.\n\n"
            . "overeenstemming: analyseer de schrijfwijze, niet de gedeelde feiten. "
            . "Waar lijken toon, woordkeuze en nadruk op elkaar, ook over politieke verschillen heen? Noem bronnen bij naam.\n\n"
            . "verschil: analyseer hoe woordkeuze, nadruk en weglatingen de gebeurtenis kleuren. "
            . "Koppel dat aan hoe bronnen schrijven (bijvoorbeeld beladen termen, wie als dader of slachtoffer wordt neergezet, welke details extra aandacht krijgen). "
            . "Noem bronnen bij naam. Gebruik geen labels als left, right of neutral.";

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
                    ],
                    'required' => ['kernfeiten', 'betrokkenen', 'overeenstemming', 'verschil'],
                    'additionalProperties' => false,
                ],
            ],
        ], $gebeurtenis->id);

        // Controleer of het antwoord een geldige JSON is
        $data = json_decode($antwoord, true);

        // Geef het antwoord terug
        return is_array($data) ? $data : [];
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
        if ($samenvatting && !$this->heeftNieuweArtikelen($gebeurtenis, $samenvatting)) {
            return $samenvatting;
        }

        // Genereer de samenvatting
        $data = $this->samenvat($gebeurtenis);

        // Controleer of de JSON geldig is
        // Stop als er een veld ontbreekt
        foreach (['kernfeiten', 'betrokkenen', 'overeenstemming', 'verschil'] as $veld) {
            if (!isset($data[$veld]) || trim((string) $data[$veld]) === '') {
                throw new \RuntimeException("Ongeldige JSON van het model: veld {$veld} ontbreekt.");
            }
        }

        // Voeg de gebeurtenis_id toe aan de data
        $data['gebeurtenis_id'] = $gebeurtenis->id;

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