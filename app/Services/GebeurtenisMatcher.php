<?php

namespace App\Services;

use App\Models\Artikel;
use App\Models\Gebeurtenis;
use Carbon\Carbon;
use DonatelloZa\RakePlus\RakePlus;
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

    // tekst die naar het embedding-model gaat: titel + begin van het artikel
    private function embeddingTekst(Artikel $artikel): string
    {
        $tekst = trim(strip_tags((string) $artikel->volledige_tekst));
        $woorden = preg_split('/\s+/', $tekst, -1, PREG_SPLIT_NO_EMPTY);

        // "clustering: " vertelt nomic-embed-text dat de vectoren bedoeld zijn om gelijke teksten te groeperen
        return trim('clustering: '.$artikel->titel.' '.implode(' ', array_slice($woorden, 0, 100)));
    }

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

        $besteKandidaat = $this->gebruiktEmbeddings($artikel)
            ? $this->besteKandidaatOpEmbedding($artikel, $kandidaten)
            : $this->besteKandidaatOpTrefwoorden($artikel, $kandidaten);

        if ($besteKandidaat !== null) {
            $artikel->update([
                'gebeurtenis_id' => $besteKandidaat->gebeurtenis_id, // <--- artikel gekoppeld aan gebeurtenis van de beste kandidaat
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
     *
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
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
