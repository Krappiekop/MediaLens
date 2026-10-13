<?php

namespace App\Services;

use App\Models\Artikel;
use App\Models\Gebeurtenis;
use Carbon\Carbon;
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

    private int $minimaleOverlap = 6;   // <--- minimale hoeveelheid trefwoorden die in beide teksten moeten voorkomen om een match te hebben

    private int $maxDagen = 0; // <--- alleen artikelen van dezelfde datum mogen aan dezelfde gebeurtenis worden gekoppeld

    private ?RakePlus $rake = null;

    private array $trefwoordCache = [];

    // koppel de artikel aan een gebeurtenis
    public function koppel(Artikel $artikel): string
    {
        if ($artikel->gebeurtenis_id !== null) { // <--- artikel is al gekoppeld aan een gebeurtenis
            return 'overgeslagen';
        }

        $zelfdeDatum = Carbon::parse($artikel->publicatiedatum)->toDateString();

        $kandidaten = Artikel::where('id', '!=', $artikel->id)          // <--- artikel is niet zelf de kandidaat
            ->whereNotNull('gebeurtenis_id')                            // <--- artikel is gekoppeld aan een gebeurtenis
            ->whereDate('publicatiedatum', $zelfdeDatum)                // <--- alleen artikelen van dezelfde dag mogen worden gekoppeld
            ->get();

        $woordenNieuw = $this->trefwoordenVan($artikel);

        $besteKandidaat = null;
        $hoogsteOverlap = 0;

        foreach ($kandidaten as $kandidaat) {
            $woordenKandidaat = $this->trefwoordenVan($kandidaat);
            $overlap = count(array_intersect($woordenNieuw, $woordenKandidaat)); // <--- vergelijk de tekst van het artikel en de kandidaat

            if ($overlap >= $this->minimaleOverlap && $overlap > $hoogsteOverlap) { // <--- onthoud de kandidaat met de meeste overlap
                $besteKandidaat = $kandidaat;
                $hoogsteOverlap = $overlap;
            }
        }

        if ($besteKandidaat !== null) {
            $artikel->update([
                'gebeurtenis_id' => $besteKandidaat->gebeurtenis_id, // <--- artikel gekoppeld aan gebeurtenis met de meeste overlap
            ]);

            return 'bestaand';
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

        if (! isset($this->trefwoordCache[$id])) {
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
