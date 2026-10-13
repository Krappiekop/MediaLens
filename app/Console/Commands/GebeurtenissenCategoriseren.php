<?php

namespace App\Console\Commands;

use App\Models\Gebeurtenis;
use App\Services\ArtikelVergelijker;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class GebeurtenissenCategoriseren extends Command
{
    protected $signature = 'gebeurtenissen:categoriseren {gebeurtenis?}';

    protected $description = 'Geeft gebeurtenissen met minstens twee artikelen en nog geen categorie een onderwerp via de AI.';

    public function handle()
    {
        $vergelijker = new ArtikelVergelijker;
        $id = $this->argument('gebeurtenis');

        // Eén id wordt altijd (opnieuw) ingedeeld, anders alleen gebeurtenissen zonder categorie
        $gebeurtenissen = $id
            ? Gebeurtenis::where('id', $id)->get()
            : Gebeurtenis::zichtbaar()->whereNull('categorie')->get();

        if ($gebeurtenissen->isEmpty()) {
            $this->info('Geen gebeurtenissen om in te delen.');

            return;
        }

        foreach ($gebeurtenissen as $gebeurtenis) {
            $gelukt = false;

            for ($poging = 1; $poging <= 3; $poging++) {
                try {
                    $categorie = $vergelijker->categoriseer($gebeurtenis);
                    $this->info("{$gebeurtenis->id}: {$categorie->label()}");
                    $gelukt = true;
                    break;

                } catch (ConnectionException $e) {
                    $this->warn("{$gebeurtenis->id}: poging {$poging} mislukt ({$e->getMessage()})");
                } catch (\RuntimeException $e) {
                    $this->warn("{$gebeurtenis->id}: poging {$poging} mislukt ({$e->getMessage()})");
                }
            }

            if (! $gelukt) {
                $this->error("{$gebeurtenis->id}: overgeslagen na 3 pogingen");
            }
        }
    }
}
