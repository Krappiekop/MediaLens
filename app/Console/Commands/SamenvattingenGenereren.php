<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
            $oudUpdated = $bestaande?->updated_at?->toDateTimeString();
            $samenvatting = $vergelijker->slaOp($gebeurtenis);

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