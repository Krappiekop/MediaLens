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

            $opgehaald = 0;
            $nieuw = 0;
            $bestaand = 0;

            foreach ($xml->channel->item as $item) {
                $titel = (string) $item->title; // titel van het artikel
                $url = (string) $item->link; // url van het artikel
                $publicatiedatum = date('Y-m-d', strtotime((string) $item->pubDate)); // publicatiedatum van het artikel

                // tekst van het artikel
                $tekst = (string) $item->description;
                // HTML-entiteiten decoderen
                $tekst = html_entity_decode($tekst, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // HTML-tags verwijderen
                $tekst = preg_replace('/<[^>]+>/', ' ', $tekst);
                // meerdere spaties vervangen door een enkele spatie
                $tekst = preg_replace('/\s+/', ' ', $tekst);
                // "Continue reading..." verwijderen
                $tekst = preg_replace('/Continue reading\.?\.?\.?\s*$/i', '', $tekst);
                // spaties aan het begin en einde verwijderen
                $tekst = trim($tekst);

                if (Artikel::where('url', $url)->exists()) { // als het artikel al bestaat, dan overslaan
                    continue;
                }

                $artikel = Artikel::create([ // maak een nieuw artikel aan
                    'titel' => $titel,
                    'publicatiedatum' => $publicatiedatum,
                    'volledige_tekst' => $tekst,
                    'url' => $url,
                    'bron_id' => $bron->id,
                ]);
                $resultaat = $matcher->koppel($artikel);

                $opgehaald++;
                if ($resultaat === 'nieuw') {
                    $nieuw++;
                } elseif ($resultaat === 'bestaand') {
                    $bestaand++;
                }
            }
            $this->line("{$opgehaald} nieuwe artikelen, {$nieuw} nieuwe gebeurtenissen, {$bestaand} bij een bestaande gebeurtenis.");
        }
    }
}
