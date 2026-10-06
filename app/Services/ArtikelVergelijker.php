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
    public function vergelijk(Gebeurtenis $gebeurtenis): string
    {
        $artikelen = $this->artikelenTekst($gebeurtenis);

        $prompt = "Hieronder staan nieuwsartikelen over dezelfde gebeurtenis, uit bronnen met verschillende politieke oriëntatie.\n\n"
            . "Vergelijk de artikelen. Benoem:\n"
            . "- taalgebruik (neutraal, emotioneel, beladen woorden)\n"
            . "- welke onderwerpen extra worden benadrukt\n"
            . "- welke actoren (personen, instanties) worden genoemd of weggelaten\n\n"
            . "Artikelen:\n\n"
            . $artikelen;

        return (new LiteLlm)->vraag($prompt);
    }

    public function samenvat(Gebeurtenis $gebeurtenis): array
    {
        // Haal de artikelen tekst op
        $artikelen = $this->artikelenTekst($gebeurtenis);

        // Maak het systeem bericht
        $systeem = "Hieronder volgen nieuwsartikelen over dezelfde gebeurtenis.\n\n"
            . "Schrijf een neutrale samenvatting die begrijpelijk is voor een breed publiek.\n"
            . "Gebruik geen politieke labels en kies geen partij.\n"
            . "Schrijf in het Nederlands.\n"
            . "Noem bij de overeenstemming en verschil de namen van de bronnen.";

        // Vraag de AI om een samenvatting te genereren met een JSON schema
        $antwoord = (new LiteLlm)->vraag($artikelen, [
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
        ], $systeem); 

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