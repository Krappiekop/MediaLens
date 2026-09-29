<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Bron;
use App\Models\Artikel;
use Illuminate\Support\Facades\Http;

class ArtikelenOphalen extends Command
{
    protected $signature = 'artikelen:ophalen';
    protected $description = 'Haalt artiekelen op bij alle bronnen met een feed-URL.';
    public function handle()
    {
        $bronnen = Bron::whereNotNull('feed_url')->get();

        foreach ($bronnen as $bron){
            $this->info("Ophalen bij {$bron->naam}...");

            $response = Http::get($bron->feed_url);

            $xml = simplexml_load_string($response->body());

            foreach($xml->channel->item as $item){
                $titel = (string) $item->title;
                $url = (string) $item->link;
                $publicatiedatum = date('Y-m-d', strtotime((string) $item->pubDate));
                $tekst = (string) $item->description;

                if(Artikel::where('url', $url)->exists()){
                    continue;
                }

                Artikel::create([
                    'titel' => $titel,
                    'publicatiedatum' => $publicatiedatum,
                    'volledige_tekst' => $tekst,
                    'url' => $url,
                    'bron_id' => $bron->id,
                ]);

                $this->line("- {$titel} ({$publicatiedatum})");
            }

            // $this->info('Aantal items gevonden: ' . count($xml->channel->item));
        }
        
    }
}
