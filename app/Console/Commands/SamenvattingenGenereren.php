<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Gebeurtenis;
use App\Services\ArtikelVergelijker;

class SamenvattingenGenereren extends Command
{
    protected $signature = 'samenvattingen:genereren {gebeurtenis?}';
    protected $description = 'Genereert samenvattingen voor gebeurtenissen met minstens twee artikelen.';
    public function handle()
    {
        $vergelijker = new ArtikelVergelijker();
        $id = $this->argument('gebeurtenis');

        $gebeurtenissen = $id
            ? Gebeurtenis::where('id', $id)->get()
            : Gebeurtenis::all();

        if ($gebeurtenissen->isEmpty()) {
            $this->error('Geen gebeurtenis gevonden.');
            return;
        }

        foreach ($gebeurtenissen as $gebeurtenis) {

            $bestaande = $gebeurtenis->samenvatting;

            $oudUpdated = $bestaande?->updated_at?->toDateTimeString(); // Tijdstip van de laatste update van de bestaande samenvatting

            $samenvatting = null; // De samenvatting die wordt gegenereerd
            $gelukt = false; // Of de samenvatting is gegenereerd

            for ($poging = 1; $poging <= 3; $poging++) {
                // Poging om de samenvatting te genereren
                try {
                    $samenvatting = $vergelijker->slaOp($gebeurtenis);
                    $gelukt = true;
                    break;

                } catch (ConnectionException | \RuntimeException $e) { // Timeout of ongeldige JSON
                    $this->warn("{$gebeurtenis->id}: poging {$poging} mislukt ({$e->getMessage()})");
                }
            }

            // Stop als er 3 pogingen mislukt zijn
            if (!$gelukt) {
                $this->error("{$gebeurtenis->id}: overgeslagen na 3 pogingen");
                continue;
            }

            if ($samenvatting === null) {
                $this->line("{$gebeurtenis->id}: overgeslagen (te weinig artikelen)");
            } elseif ($bestaande === null) {
                $this->info("{$gebeurtenis->id}: opgeslagen (samenvatting {$samenvatting->id})");
            } elseif ($samenvatting->updated_at->toDateTimeString() !== $oudUpdated) {
                $this->info("{$gebeurtenis->id}: vernieuwd (samenvatting {$samenvatting->id})");
            } else {
                $this->line("{$gebeurtenis->id}: bestaat al (samenvatting {$samenvatting->id})");
            }
        }
    }
}