<?php

namespace App\Console\Commands;

use App\Models\Artikel;
use App\Services\GebeurtenisMatcher;
use Illuminate\Console\Command;

class ArtikelenEmbeddings extends Command
{
    protected $signature = 'artikelen:embeddings';

    protected $description = 'Maakt via Ollama embeddings voor bestaande artikelen die er nog geen hebben.';

    public function handle(): int
    {
        $matcher = new GebeurtenisMatcher;
        $totaal = Artikel::whereNull('embedding')->count();
        $gemaakt = 0;

        $this->info("{$totaal} artikelen zonder embedding.");

        // per 50 artikelen één aanroep naar Ollama
        foreach (Artikel::whereNull('embedding')->lazyById(50)->chunk(50) as $batch) {
            $aantal = $matcher->maakEmbeddings($batch);
            $gemaakt += $aantal;

            if ($aantal < $batch->count()) {
                $this->error('Ollama gaf geen embeddings terug. Draait Ollama en is het model gepulld? (ollama pull nomic-embed-text)');

                return self::FAILURE;
            }

            $this->line("{$gemaakt}/{$totaal} klaar");
        }

        return self::SUCCESS;
    }
}
