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
        $artikelen = $this->artikelenTekst($gebeurtenis);

        $prompt = "Hieronder staan nieuwsartikelen over dezelfde gebeurtenis.\n\n"
            . "Schrijf een neutrale samenvatting die begrijpelijk is voor een breed publiek.\n"
            . "Gebruik geen politieke labels en kies geen partij.\n"
            . "Noem bij de overeenstemming en verschil de namen van de bronnen.\n\n"
            . "Antwoord alleen met geldige JSON, zonder markdown, met precies deze keys:\n"
            . '{"kernfeiten":"","betrokkenen":"","overeenstemming":"","verschil":""}'
            . "\n\nArtikelen:\n\n"
            . $artikelen;

        $antwoord = (new LiteLlm)->vraag($prompt);
        $data = json_decode($antwoord, true);
        return is_array($data) ? $data : [];
    }

    public function slaOp(Gebeurtenis $gebeurtenis): ?Samenvatting
    {
        $samenvatting = $gebeurtenis->samenvatting;
        $aantal = $gebeurtenis->artikelen()->count();

        if ($aantal < $this->minimaleArtikelen) {
            return $samenvatting;
        }

        if ($samenvatting && !$this->heeftNieuweArtikelen($gebeurtenis, $samenvatting)) {
            return $samenvatting;
        }

        $data = $this->samenvat($gebeurtenis);
        $data['gebeurtenis_id'] = $gebeurtenis->id;
        
        if ($samenvatting) {
            $samenvatting->update($data);
            return $samenvatting;
        }
        return Samenvatting::create($data);
    }
    private function heeftNieuweArtikelen(Gebeurtenis $gebeurtenis, Samenvatting $samenvatting): bool
    {
        $nieuwste = $gebeurtenis->artikelen()->max('created_at');
        return $nieuwste > $samenvatting->updated_at;
    }
}