<?php

namespace App\Services;

use App\Models\Gebeurtenis;
use App\Services\LiteLlm;

class ArtikelVergelijker
{
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
}