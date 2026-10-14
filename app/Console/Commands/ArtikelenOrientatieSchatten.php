<?php

namespace App\Console\Commands;

use App\Models\Artikel;
use App\Models\Bron;
use App\Services\OrientatieSchatter;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class ArtikelenOrientatieSchatten extends Command
{
    protected $signature = 'artikelen:orientatie-schatten
        {--bron= : Alleen artikelen van deze bron (id of naam)}
        {--limit=20 : Maximaal aantal artikelen, om de kosten te beperken}
        {--opnieuw : Ook artikelen die al een schatting hebben}';

    protected $description = 'Laat de AI de politieke oriëntatie van artikelen schatten, naast het MBFC-label van de bron.';

    public function handle(): int
    {
        $schatter = new OrientatieSchatter;
        $query = Artikel::query()->latest('publicatiedatum');

        if ($this->option('bron') !== null) {
            $bron = Bron::where('id', $this->option('bron'))->orWhere('naam', $this->option('bron'))->first();

            if ($bron === null) {
                $this->error("Bron {$this->option('bron')} bestaat niet.");

                return self::FAILURE;
            }

            $query->where('bron_id', $bron->id);
        }

        if (! $this->option('opnieuw')) {
            $query->whereNull('ai_orientatie');
        }

        $artikelen = $query->limit((int) $this->option('limit'))->get();

        if ($artikelen->isEmpty()) {
            $this->info('Geen artikelen om te schatten.');

            return self::SUCCESS;
        }

        foreach ($artikelen as $artikel) {
            $gelukt = false;

            for ($poging = 1; $poging <= 3; $poging++) {
                try {
                    $schatter->slaOp($artikel);
                    $label = $artikel->ai_politiek ? $artikel->ai_orientatie : 'geen politieke lading';
                    $this->info("{$artikel->id}: {$label} ({$artikel->ai_zekerheid}%) {$artikel->titel}");
                    $gelukt = true;
                    break;

                } catch (ConnectionException $e) {
                    $this->warn("{$artikel->id}: poging {$poging} mislukt ({$e->getMessage()})");
                } catch (\RuntimeException $e) {
                    $this->warn("{$artikel->id}: poging {$poging} mislukt ({$e->getMessage()})");
                }
            }

            if (! $gelukt) {
                $this->error("{$artikel->id}: overgeslagen na 3 pogingen");
            }
        }

        return self::SUCCESS;
    }
}
