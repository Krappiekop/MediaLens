<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\Artikel;
use App\Models\Gebeurtenis;

class GebeurtenisMatcher
{
    // stopwoorden die niet meegehaald worden in de trefwoorden
    private array $stopwoorden = [
        'a', 'an', 'and', 'as', 'at', 'be',
        'by', 'for', 'from', 'in', 'is',
        'it', 'its', 'of', 'on', 'or', 's', 
        'says', 'the', 'this', 'to', 'with',
    ];

    private function vergelijkTekst(Artikel $artikel): string
    {
        $tekst = trim(strip_tags((string) $artikel->volledige_tekst));
        return $tekst !== '' ? $artikel->volledige_tekst : $artikel->titel;
    }

    private int $minimaleOverlap = 7;                               // <--- minimale hoeveelheid trefwoorden die in beide teksten moeten voorkomen om een match te hebben (default 2)
    private int $maxDagen = 3;

    // koppel de artikel aan een gebeurtenis
    public function koppel(Artikel $artikel): void
    {
        if ($artikel->gebeurtenis_id !== null) { // <--- artikel is al gekoppeld aan een gebeurtenis
            return;
        }

        $vanaf = Carbon::parse($artikel->publicatiedatum)->subDays($this->maxDagen)->toDateString();
        $tot = Carbon::parse($artikel->publicatiedatum)->addDays($this->maxDagen)->toDateString();

        $kandidaten = Artikel::where('id', '!=', $artikel->id)          // <--- artikel is niet zelf de kandidaat
            ->whereNotNull('gebeurtenis_id')                            // <--- artikel is gekoppeld aan een gebeurtenis
            ->whereBetween('publicatiedatum', [$vanaf, $tot])           // <--- artikel publicatiedatum tussen $vanaf en $tot (max 3 dagen verschil)
            ->get();

        foreach ($kandidaten as $kandidaat) {
            if ($this->zelfdeGebeurtenis(
                $this->vergelijkTekst($artikel), 
                $this->vergelijkTekst($kandidaat)
                )) { // <--- vergelijk de tekst van het artikel en de kandidaat
                $artikel->update([
                    'gebeurtenis_id' => $kandidaat->gebeurtenis_id, // <--- artikel gekoppeld aan gebeurtenis
                ]);

                echo "Match: \"{$artikel->titel}\" -> gebeurtenis {$kandidaat->gebeurtenis_id}\n";
                return;
            }
        }

        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => $artikel->titel, // <--- nieuwe gebeurtenis aangemaakt met de titel van het artikel
        ]);

        $artikel->update([
            'gebeurtenis_id' => $gebeurtenis->id, // <--- artikel gekoppeld aan gebeurtenis
        ]);

        echo "Nieuwe gebeurtenis: {$gebeurtenis->id}: \"{$gebeurtenis->onderwerp}\"\n";
    }

    // trefwoorden uit de titel halen
    public function trefwoorden(string $titel): array
    {
        $titel = html_entity_decode(strip_tags($titel), ENT_QUOTES | ENT_HTML5, 'UTF-8'); // <--- tags en entiteiten verwijderen
        $titel = mb_strtolower($titel); // <--- titel omgezet naar lowercase
        $woorden = preg_split('/[^\p{L}\p{N}]+/u', $titel, -1, PREG_SPLIT_NO_EMPTY);

        $woorden = array_filter($woorden, function ($woord) {       // <--- array_filter om te filteren
            return !in_array($woord, $this->stopwoorden, true)     // <--- stopwoorden verwijderen
                && mb_strlen($woord) > 1;                           // <--- woorden korter dan 2 letters verwijderen
        });

        return array_values(array_unique($woorden));
    }

    // trefwoorden die in beide titels voorkomen
    public function overlap(string $titelA, string $titelB): array
    {
        // trefwoorden uit de titels halen
        $woordenA = $this->trefwoorden($titelA);
        $woordenB = $this->trefwoorden($titelB);

        return array_values(array_intersect($woordenA, $woordenB)); // <--- trefwoorden die in beide titels voorkomen
    }

    // controleren of de overlap voldoende is
    public function zelfdeGebeurtenis(string $titelA, string $titelB): bool
    {
        return count($this->overlap($titelA, $titelB)) >= $this->minimaleOverlap;
    }
}


// php Artisan Tinker

// $m->zelfdeGebeurtenis(
//     "Trump Signs New Tariff Bill After Senate Vote",
//     "Senate Passes Trump Tariff Bill, Democrats Object"
// );

// $m->zelfdeGebeurtenis(
//     "Trump Signs New Tariff Bill After Senate Vote",
//     "Ukraine Peace Talks Stall in Istanbul"
// );