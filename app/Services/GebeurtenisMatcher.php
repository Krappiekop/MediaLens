<?php

namespace App\Services;

use App\Models\Artikel;
use App\Models\Gebeurtenis;
use Carbon\Carbon;
use DonatelloZa\RakePlus\RakePlus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class GebeurtenisMatcher
{
    public function __construct(private Ollama $ollama = new Ollama) {}

    /**
     * Maakt in batches embeddings voor artikelen die er nog geen hebben.
     * Lukt dat niet (bijv. Ollama staat uit), dan blijft de embedding leeg en valt koppel() terug op RAKE.
     *
     * @param  iterable<Artikel>  $artikelen
     * @return int aantal artikelen dat een embedding heeft gekregen
     */
    public function maakEmbeddings(iterable $artikelen): int
    {
        $zonderEmbedding = collect($artikelen)->filter(fn (Artikel $artikel) => $artikel->embedding === null);
        $aantal = 0;

        foreach ($zonderEmbedding->chunk(50) as $batch) {
            try {
                $embeddings = $this->ollama->embeddings($batch->map(fn (Artikel $artikel) => $this->embeddingTekst($artikel))->values()->all());
            } catch (ConnectionException|\RuntimeException $e) {
                Log::warning('embeddings maken mislukt, terugval op RAKE', ['fout' => $e->getMessage()]);

                return $aantal;
            }

            foreach ($batch->values() as $index => $artikel) {
                $artikel->update(['embedding' => $embeddings[$index]]);
                $aantal++;
            }
        }

        return $aantal;
    }

    /**
     * Tekst die naar het embedding-model gaat: titel plus de eerste 100 woorden. Meer dan de 40 van RAKE, omdat een
     * embedding naar de hele betekenis kijkt en minder last heeft van een gedeelde promozin. (Notitie 16.)
     */
    private function embeddingTekst(Artikel $artikel): string
    {
        $tekst = trim(strip_tags((string) $artikel->volledige_tekst));
        $woorden = preg_split('/\s+/', $tekst, -1, PREG_SPLIT_NO_EMPTY);

        // "clustering: " vertelt nomic-embed-text dat de vectoren bedoeld zijn om gelijke teksten te groeperen
        return trim('clustering: '.$artikel->titel.' '.implode(' ', array_slice($woorden, 0, 100)));
    }

    /**
     * Tekst voor de RAKE-route: de eerste $leadWoorden woorden van de RSS-omschrijving, of de titel als die leeg is.
     * Niet de titel zelf: koppen over hetzelfde nieuws verschillen per bron te veel om op gedeelde woorden te matchen.
     */
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

        return implode(' ', array_slice($woorden, 0, $this->leadWoorden));
    }

    /**
     * Alleen de lead telt mee bij RAKE. Met de hele omschrijving deelden lange stukken (zoals de Guardian-digest
     * First Thing) genoeg algemeen journalistiek Engels ("said", "tuesday", "according") met SCMP of NDTV om de
     * drempel te halen, en kwamen losse onderwerpen in één gebeurtenis. In de eerste 40 woorden staat waar het
     * artikel over gaat. De volledige tekst blijft in de database voor de AI. (Notitie 3.)
     */
    private int $leadWoorden = 40;

    /**
     * Minimaal aantal gedeelde RAKE-trefwoorden voor een match (alleen de RAKE-route). Gekozen op de echte feeds na de
     * overstap naar RAKE: bij 7 bleven artikelen over hetzelfde nieuws los, bij 5 kwamen ongerelateerde onderwerpen
     * samen. Het is een knop op deze set bronnen, geen vaste waarde. (Notitie 3.)
     */
    private int $minimaleOverlap = 6;

    /**
     * Venster rond de publicatietijd waarbinnen kandidaten liggen, in beide richtingen. Tot bouwblok 17 was dat dezelfde
     * kalenderdag, omdat publicatiedatum geen tijd had; daardoor kwam een artikel van 23:50 nooit bij een van 00:10, en
     * viel nieuws dat de volgende dag doorliep uiteen. 48 uur vangt dat op. Langer maakt het risico groter dat een nieuw
     * verhaal over hetzelfde onderwerp (een tweede drone-aanval, een volgende rechtszaak) in de oude groep belandt.
     * De beste-kandidaat-regel en de drempel van 0,85 beperken dat. Verhalen die langer lopen, verbinden de
     * gerelateerde gebeurtenissen op de detailpagina. (Notitie 13 en 17.)
     */
    private int $maxUren = 48;

    private ?RakePlus $rake = null;

    private array $trefwoordCache = [];

    // koppel de artikel aan een gebeurtenis
    public function koppel(Artikel $artikel): string
    {
        if ($artikel->gebeurtenis_id !== null) { // <--- artikel is al gekoppeld aan een gebeurtenis
            return 'overgeslagen';
        }

        $publicatie = Carbon::parse($artikel->publicatiedatum);
        $gebruiktEmbeddings = $this->gebruiktEmbeddings($artikel);

        /*
         * In het venster van 48 uur liggen duizenden artikelen, elk met een embedding van ±9,5 KB JSON. Alles in één keer
         * ophalen liep in oktober 2026 boven de 128 MB van PHP (bouwblok 19). Daarom alleen de nodige kolommen,
         * in porties van 500 op id.
         */
        $kandidaten = Artikel::query()
            ->select($gebruiktEmbeddings ? ['id', 'gebeurtenis_id', 'embedding'] : ['id', 'gebeurtenis_id', 'titel', 'volledige_tekst'])
            ->where('id', '!=', $artikel->id)                           // <--- artikel is niet zelf de kandidaat
            ->whereNotNull('gebeurtenis_id')                            // <--- artikel is gekoppeld aan een gebeurtenis
            ->whereBetween('publicatiedatum', [                         // <--- alleen artikelen binnen het venster, ook over middernacht heen
                $publicatie->copy()->subHours($this->maxUren),
                $publicatie->copy()->addHours($this->maxUren),
            ])
            ->lazyById(500);

        $besteKandidaat = $gebruiktEmbeddings
            ? $this->besteKandidaatOpEmbedding($artikel, $kandidaten)
            : $this->besteKandidaatOpTrefwoorden($artikel, $kandidaten);

        if ($besteKandidaat !== null) {
            $artikel->update([
                'gebeurtenis_id' => $besteKandidaat->gebeurtenis_id, // <--- artikel gekoppeld aan gebeurtenis van de beste kandidaat
            ]);

            $this->werkGebeurtenisBij($besteKandidaat->gebeurtenis); // <--- met een artikel erbij kan een andere titel centraler zijn

            return 'bestaand';
        }

        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => $artikel->titel, // <--- nieuwe gebeurtenis aangemaakt met de titel van het artikel
        ]);

        $artikel->update([
            'gebeurtenis_id' => $gebeurtenis->id, // <--- artikel gekoppeld aan gebeurtenis
        ]);

        $this->werkGebeurtenisBij($gebeurtenis);

        return 'nieuw';
    }

    /**
     * Werkt de afgeleide velden van een gebeurtenis bij nadat er een artikel bij is gekomen:
     * de meest centrale titel en de gemiddelde embedding (voor gerelateerde gebeurtenissen).
     */
    public function werkGebeurtenisBij(Gebeurtenis $gebeurtenis): void
    {
        $artikelen = $gebeurtenis->artikelen()
            ->whereNotNull('embedding')
            ->orderBy('id')
            ->get(['id', 'titel', 'embedding']);

        if ($artikelen->isEmpty()) {
            return;
        }

        $gebeurtenis->embedding = $this->gemiddeldeEmbedding($artikelen->pluck('embedding')->all());
        $this->kiesCentraleTitel($gebeurtenis, $artikelen);
        $gebeurtenis->save();
    }

    /**
     * Gebeurtenissen die het meest lijken op deze, op basis van de gemiddelde embedding, mits boven de minimale similarity.
     * De gebeurtenis zelf en gebeurtenissen met minder dan 2 artikelen tellen niet mee.
     *
     * @return list<int> ids, meest gelijkend eerst
     */
    public function gerelateerdeGebeurtenissen(Gebeurtenis $gebeurtenis, int $aantal = 3): array
    {
        if ($gebeurtenis->embedding === null) {
            return [];
        }

        $minimaleSimilarity = (float) config('services.matcher.minimale_similarity_gerelateerd');
        $scores = [];

        foreach (Gebeurtenis::zichtbaar()->whereKeyNot($gebeurtenis->id)->whereNotNull('embedding')->get(['id', 'embedding']) as $ander) {
            $similarity = $this->cosineSimilarity($gebeurtenis->embedding, $ander->embedding);

            if ($similarity >= $minimaleSimilarity) {
                $scores[$ander->id] = $similarity;
            }
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, $aantal);
    }

    /**
     * @param  list<list<float>>  $embeddings
     * @return list<float>
     */
    private function gemiddeldeEmbedding(array $embeddings): array
    {
        $som = array_fill(0, count($embeddings[0]), 0.0);

        foreach ($embeddings as $embedding) {
            foreach ($embedding as $index => $waarde) {
                $som[$index] += $waarde;
            }
        }

        return array_map(fn (float|int $waarde): float => $waarde / count($embeddings), $som);
    }

    /**
     * Maakt de titel van het meest centrale artikel het onderwerp van de gebeurtenis: het artikel waarvan de embedding
     * gemiddeld het meest lijkt op die van de andere artikelen. Bij gelijke score wint het oudste artikel.
     * Met minder dan 2 embeddings is er niets te vergelijken en blijft het onderwerp staan.
     *
     * @param  Collection<int, Artikel>  $artikelen  artikelen met embedding, oudste eerst
     */
    private function kiesCentraleTitel(Gebeurtenis $gebeurtenis, Collection $artikelen): void
    {
        if ($artikelen->count() < 2) {
            return;
        }

        $centraalsteArtikel = null;
        $hoogsteScore = -INF;

        foreach ($artikelen as $artikel) {
            $score = 0.0;

            foreach ($artikelen as $ander) {
                if ($ander->id !== $artikel->id) {
                    $score += $this->cosineSimilarity($artikel->embedding, $ander->embedding);
                }
            }

            if ($score > $hoogsteScore) {
                $centraalsteArtikel = $artikel;
                $hoogsteScore = $score;
            }
        }

        $gebeurtenis->onderwerp = $centraalsteArtikel->titel;
    }

    // embeddings gebruiken als dat is ingesteld én het artikel er een heeft (anders terugval op RAKE)
    private function gebruiktEmbeddings(Artikel $artikel): bool
    {
        return config('services.matcher.methode') === 'embeddings' && $artikel->embedding !== null;
    }

    /**
     * Kandidaat waarvan de embedding het meest lijkt op die van het artikel, mits boven de minimale similarity.
     *
     * @param  iterable<Artikel>  $kandidaten
     */
    private function besteKandidaatOpEmbedding(Artikel $artikel, iterable $kandidaten): ?Artikel
    {
        $besteKandidaat = null;
        $hoogsteSimilarity = (float) config('services.matcher.minimale_similarity');

        foreach ($kandidaten as $kandidaat) {
            if ($kandidaat->embedding === null) {
                continue;
            }

            $similarity = $this->cosineSimilarity($artikel->embedding, $kandidaat->embedding);

            if ($similarity >= $hoogsteSimilarity) { // <--- onthoud de kandidaat met de hoogste similarity
                $besteKandidaat = $kandidaat;
                $hoogsteSimilarity = $similarity;
            }
        }

        return $besteKandidaat;
    }

    /**
     * Kandidaat met de meeste gedeelde RAKE-trefwoorden, mits minimaal $minimaleOverlap.
     *
     * @param  iterable<Artikel>  $kandidaten
     */
    private function besteKandidaatOpTrefwoorden(Artikel $artikel, iterable $kandidaten): ?Artikel
    {
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

        return $besteKandidaat;
    }

    /**
     * Cosine similarity tussen twee vectoren: 1 = zelfde betekenis, 0 = niets met elkaar te maken.
     * Vectoren van verschillende lengte (bijv. na een wissel van OLLAMA_EMBEDDING_MODEL) zijn niet te vergelijken: 0.
     *
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $inproduct = 0.0;
        $lengteA = 0.0;
        $lengteB = 0.0;

        foreach ($a as $index => $waarde) {
            $inproduct += $waarde * $b[$index];
            $lengteA += $waarde * $waarde;
            $lengteB += $b[$index] * $b[$index];
        }

        if ($lengteA == 0.0 || $lengteB == 0.0) {
            return 0.0;
        }

        return $inproduct / (sqrt($lengteA) * sqrt($lengteB));
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
