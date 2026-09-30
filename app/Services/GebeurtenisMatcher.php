<?php
namespace App\Services;
use App\Models\Artikel;
class GebeurtenisMatcher
{
    // stopwoorden die niet meegehaald worden in de trefwoorden
    private array $stopwoorden = [
        'a',
        'an',
        'and',
        'as',
        'at',
        'be',
        'by',
        'for',
        'from',
        'in',
        'is',
        'it',
        'its',
        'of',
        'on',
        'or',
        's',
        'says',
        'the',
        'this',
        'to',
        'with',
    ];
    private int $minimaleOverlap = 2;                               // <--- minimale hoeveelheid trefwoorden die in beide titels moeten voorkomen om een match te hebben (default 2)

    // koppel de artikel aan een gebeurtenis
    public function koppel(Artikel $artikel): void
    {

        // Voorlopig alleen een teken van leven.
        echo "Matcher aangeroepen voor: {$artikel->titel}\n";
    }

    // trefwoorden uit de titel halen
    public function trefwoorden(string $titel): array
    {
        $titel = mb_strtolower($titel);
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