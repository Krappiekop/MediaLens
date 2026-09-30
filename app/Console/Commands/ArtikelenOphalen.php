<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use App\Models\Bron;
use App\Models\Artikel;
use App\Services\GebeurtenisMatcher;


class ArtikelenOphalen extends Command
{
    protected $signature = 'artikelen:ophalen';
    protected $description = 'Haalt artikelen op bij alle bronnen met een werkende feed-URL.';
    public function handle()
    {
        $bronnen = Bron::whereNotNull('feed_url')->get();

        $matcher = new GebeurtenisMatcher();

        foreach ($bronnen as $bron) {
            $this->info("Ophalen bij {$bron->naam}...");

            // bij fout, geen verbinding, dan error en continue met de volgende bron
            try {
                $response = Http::get($bron->feed_url);
            } catch (ConnectionException $e) {
                $this->error("Geen verbinding met {$bron->naam}: {$e->getMessage()}");
                continue;
            }

            // bij fout, 4xx- of 5xx-respons, dan error en continue met de volgende bron
            if ($response->failed()) {
                $this->error("Fout bij ophalen bij {$bron->naam}: {$response->status()}");
                continue;
            }

            // bij lege XML, dan error en continue met de volgende bron
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                $this->error("Lege XML van {$bron->naam}, bron overgeslagen.");
                continue;
            }

            foreach ($xml->channel->item as $item) {
                $titel = (string) $item->title;
                $url = (string) $item->link;
                $publicatiedatum = date('Y-m-d', strtotime((string) $item->pubDate));
                $tekst = (string) $item->description;

                if (Artikel::where('url', $url)->exists()) {
                    continue;
                }

                $artikel = Artikel::create([
                    'titel' => $titel,
                    'publicatiedatum' => $publicatiedatum,
                    'volledige_tekst' => $tekst,
                    'url' => $url,
                    'bron_id' => $bron->id,
                ]);
                $matcher->koppel($artikel);

                $this->line("- {$titel} ({$publicatiedatum})");

            }
            // $this->info("Aantal nieuwe items gevonden: " . count($xml->channel->item));
        }

    }
}
