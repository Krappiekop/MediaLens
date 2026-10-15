<?php

namespace App\Console\Commands;

use App\Models\Artikel;
use App\Models\Bron;
use App\Services\OrientatieSchatter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class OrientatieVergelijken extends Command
{
    protected $signature = 'orientatie:vergelijken
        {--min=1 : Alleen bronnen met minstens zoveel politiek geladen, geschatte artikelen}';

    protected $description = 'Zet per bron het MBFC-label naast de AI-schatting: overeenstemming per bakje, confusion matrix en grootste afwijkingen. Alleen uit de database, geen AI-calls.';

    private const BAKJES = ['Links', 'Midden', 'Rechts'];

    public function handle(): int
    {
        $vergelijkingen = $this->vergelijkingenPerBron((int) $this->option('min'));

        if ($vergelijkingen->isEmpty()) {
            $this->info('Geen bronnen met AI-schattingen om te vergelijken.');

            return self::SUCCESS;
        }

        $this->toonPerBron($vergelijkingen);
        $this->toonOvereenstemmingPerBakje($vergelijkingen);
        $this->toonConfusionMatrix();

        return self::SUCCESS;
    }

    /**
     * MBFC-label en AI-schatting per bron, de grootste afwijking eerst.
     *
     * @return Collection<int, array{naam: string, mbfc: string, ai: string, mbfc_bakje: string, ai_bakje: string, afwijking: float, score: float, aantal: int, zekerheid: int}>
     */
    private function vergelijkingenPerBron(int $minimumAantal): Collection
    {
        return Bron::query()
            ->orderBy('naam')
            ->get()
            ->map(function (Bron $bron): ?array {
                $schatting = $bron->aiSchatting();

                if ($schatting === null || ! in_array($bron->orientatie, OrientatieSchatter::LABELS, true)) {
                    return null;
                }

                $mbfcScore = array_search($bron->orientatie, OrientatieSchatter::LABELS, true) - 2;

                return [
                    'naam' => $bron->naam,
                    'mbfc' => $bron->orientatie,
                    'ai' => $schatting['orientatie'],
                    'mbfc_bakje' => $bron->bakje(),
                    'ai_bakje' => Bron::bakjeVan($schatting['orientatie']),
                    'afwijking' => round($schatting['score'] - $mbfcScore, 1),
                    'score' => $schatting['score'],
                    'aantal' => $schatting['aantal'],
                    'zekerheid' => $schatting['gemiddelde_zekerheid'],
                ];
            })
            ->filter(fn (?array $vergelijking): bool => $vergelijking !== null && $vergelijking['aantal'] >= $minimumAantal)
            ->sortByDesc(fn (array $vergelijking): float => abs($vergelijking['afwijking']))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $vergelijkingen
     */
    private function toonPerBron(Collection $vergelijkingen): void
    {
        $this->line('Per bron (afwijking = AI-score min MBFC-score, van -4 tot 4; negatief = AI linkser)');
        $this->table(
            ['Bron', 'MBFC', 'AI', 'Afwijking', 'Artikelen', 'Zekerheid', 'Zelfde bakje'],
            $vergelijkingen->map(fn (array $vergelijking): array => [
                $vergelijking['naam'],
                $vergelijking['mbfc'],
                $vergelijking['ai'],
                sprintf('%+.1f', $vergelijking['afwijking']),
                $vergelijking['aantal'],
                "{$vergelijking['zekerheid']}%",
                $vergelijking['mbfc_bakje'] === $vergelijking['ai_bakje'] ? 'ja' : 'nee',
            ]),
        );
    }

    /**
     * Per MBFC-bakje: bij hoeveel bronnen komt de AI in hetzelfde bakje uit.
     *
     * @param  Collection<int, array<string, mixed>>  $vergelijkingen
     */
    private function toonOvereenstemmingPerBakje(Collection $vergelijkingen): void
    {
        $rijen = collect([...self::BAKJES, 'Totaal'])->map(function (string $bakje) use ($vergelijkingen): array {
            $groep = $bakje === 'Totaal' ? $vergelijkingen : $vergelijkingen->where('mbfc_bakje', $bakje);
            $zelfde = $groep->filter(fn (array $vergelijking): bool => $vergelijking['mbfc_bakje'] === $vergelijking['ai_bakje'])->count();

            return [
                $bakje,
                $groep->count(),
                $zelfde,
                $groep->isEmpty() ? '-' : round($zelfde / $groep->count() * 100).'%',
                $groep->isEmpty() ? '-' : sprintf('%+.1f', $groep->avg('afwijking')),
            ];
        });

        $this->newLine();
        $this->line('Overeenstemming per MBFC-bakje (bronnen)');
        $this->table(['MBFC', 'Bronnen', 'Zelfde bakje', 'Overeenstemming', 'Gem. afwijking'], $rijen);
    }

    /**
     * Per artikel: MBFC-bakje van de bron tegen het AI-bakje van het artikel.
     * Zo is te zien of de AI binnen elke MBFC-groep naar links of rechts trekt,
     * los van hoeveel linkse of rechtse bronnen MediaLens volgt.
     */
    private function toonConfusionMatrix(): void
    {
        $kolommen = [...self::BAKJES, 'Geen lading'];
        $matrix = array_fill_keys(self::BAKJES, array_fill_keys($kolommen, 0));

        Artikel::query()
            ->with('bron:id,orientatie')
            ->whereNotNull('ai_orientatie')
            ->get(['id', 'bron_id', 'ai_orientatie', 'ai_politiek'])
            ->each(function (Artikel $artikel) use (&$matrix): void {
                $mbfcBakje = $artikel->bron?->bakje();

                if (isset($matrix[$mbfcBakje])) {
                    $matrix[$mbfcBakje][$artikel->aiBakje()]++;
                }
            });

        $this->newLine();
        $this->line('Confusion matrix per artikel (rij = MBFC-bakje van de bron, kolom = AI-bakje van het artikel)');
        $this->table(
            ['MBFC \\ AI', ...$kolommen, 'Totaal'],
            collect($matrix)->map(fn (array $rij, string $bakje): array => [$bakje, ...array_values($rij), array_sum($rij)])->values(),
        );
    }
}
