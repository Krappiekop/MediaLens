<?php

namespace App\Console\Commands;

use App\Models\Gebeurtenis;
use App\Services\GebeurtenisMatcher;
use Illuminate\Console\Command;

class GebeurtenissenBijwerken extends Command
{
    protected $signature = 'gebeurtenissen:bijwerken {gebeurtenis?}';

    protected $description = 'Kiest de meest centrale titel als onderwerp en berekent de gemiddelde embedding van gebeurtenissen.';

    public function handle(): int
    {
        $matcher = new GebeurtenisMatcher;
        $id = $this->argument('gebeurtenis');

        $gebeurtenissen = $id
            ? Gebeurtenis::where('id', $id)->get()
            : Gebeurtenis::all();

        $aangepast = 0;

        foreach ($gebeurtenissen as $gebeurtenis) {
            $oudeTitel = $gebeurtenis->onderwerp;
            $matcher->werkGebeurtenisBij($gebeurtenis);

            if ($gebeurtenis->onderwerp !== $oudeTitel) {
                $this->line("{$gebeurtenis->id}: {$gebeurtenis->onderwerp}");
                $aangepast++;
            }
        }

        $this->info("{$gebeurtenissen->count()} gebeurtenissen bijgewerkt, {$aangepast} titels aangepast.");

        return self::SUCCESS;
    }
}
