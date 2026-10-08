<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\Artikel;
use App\Models\Gebeurtenis;
use DonatelloZa\RakePlus\RakePlus;

class GebeurtenisMatcher
{


    private function vergelijkTekst(Artikel $artikel): string
    {
        // tekst van het artikel
        $tekst = trim(strip_tags((string) $artikel->volledige_tekst));

        // als de tekst leeg is, dan gebruik de titel
        if ($tekst === '') {
            return $artikel->titel;
        }

        // woorden uit de tekst halen
        $woorden = preg_split('/\s+/', $tekst, -1, PREG_SPLIT_NO_EMPTY);
        // de eerste 40 woorden nemen

        return implode(' ', array_slice($woorden, 0, 40));
    }

    private int $minimaleOverlap = 5;   // <--- minimale hoeveelheid trefwoorden die in beide teksten moeten voorkomen om een match te hebben
    private int $maxDagen = 1;
    private ?RakePlus $rake = null;
    private array $trefwoordCache = [];

    // koppel de artikel aan een gebeurtenis
    public function koppel(Artikel $artikel): string
    {
        if ($artikel->gebeurtenis_id !== null) { // <--- artikel is al gekoppeld aan een gebeurtenis
            return 'overgeslagen';
        }

        $vanaf = Carbon::parse($artikel->publicatiedatum)->subDays($this->maxDagen)->toDateString();
        $tot = Carbon::parse($artikel->publicatiedatum)->addDays($this->maxDagen)->toDateString();

        $kandidaten = Artikel::where('id', '!=', $artikel->id)          // <--- artikel is niet zelf de kandidaat
            ->whereNotNull('gebeurtenis_id')                            // <--- artikel is gekoppeld aan een gebeurtenis
            ->whereBetween('publicatiedatum', [$vanaf, $tot])           // <--- artikel publicatiedatum tussen $vanaf en $tot (max 3 dagen verschil)
            ->get();

        $woordenNieuw = $this->trefwoordenVan($artikel);

        foreach ($kandidaten as $kandidaat) {
            $woordenKandidaat = $this->trefwoordenVan($kandidaat);

            if (count(array_intersect($woordenNieuw, $woordenKandidaat)) >= $this->minimaleOverlap) { // <--- vergelijk de tekst van het artikel en de kandidaat
                $artikel->update([
                    'gebeurtenis_id' => $kandidaat->gebeurtenis_id, // <--- artikel gekoppeld aan gebeurtenis
                ]);
                return 'bestaand';
            }
        }

        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => $artikel->titel, // <--- nieuwe gebeurtenis aangemaakt met de titel van het artikel
        ]);

        $artikel->update([
            'gebeurtenis_id' => $gebeurtenis->id, // <--- artikel gekoppeld aan gebeurtenis
        ]);

        return 'nieuw';
    }


    // trefwoorden uit de titel halen
    public function trefwoorden(string $titel): array
    {
        $titel = html_entity_decode(strip_tags($titel), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($this->rake === null) {
            $this->rake = RakePlus::create($titel, 'en_US');

            return $this->rake->keywords();
        }

        return $this->rake->extract($titel)->keywords();
    }

    private function trefwoordenVan(Artikel $artikel): array
    {
        $id = $artikel->id;

        if (!isset($this->trefwoordCache[$id])) {
            $this->trefwoordCache[$id] = $this->trefwoorden($this->vergelijkTekst($artikel));
        }

        return $this->trefwoordCache[$id];
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