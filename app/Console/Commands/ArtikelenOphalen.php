<?php

namespace App\Console\Commands;

use App\Models\Artikel;
use App\Models\Bron;
use App\Services\GebeurtenisMatcher;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class ArtikelenOphalen extends Command
{
    protected $signature = 'artikelen:ophalen';

    protected $description = 'Haalt artikelen op bij alle bronnen met een werkende feed-URL.';

    public function handle(): int
    {
        $bronnen = Bron::whereNotNull('feed_url')->get();

        $matcher = new GebeurtenisMatcher;

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

            $nieuweArtikelen = [];

            foreach ($xml->channel->item as $item) {
                $titel = (string) $item->title; // titel van het artikel
                $url = (string) $item->link; // url van het artikel
                $publicatiedatum = date('Y-m-d', strtotime((string) $item->pubDate)); // publicatiedatum van het artikel

                // tekst van het artikel
                $tekst = (string) $item->description;
                // HTML-entiteiten decoderen
                $tekst = html_entity_decode($tekst, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // "Get our" verwijderen
                $tekst = preg_replace('/<li>\s*<p>\s*Get our\b.*?<\/li>/is', ' ', $tekst);
                // "Listen to the podcast" verwijderen
                $tekst = preg_replace('/<li>\s*<p>\s*Listen to the podcast\b.*?<\/li>/is', ' ', $tekst);
                // HTML-tags verwijderen
                $tekst = preg_replace('/<[^>]+>/', ' ', $tekst);
                // meerdere spaties vervangen door een enkele spatie
                $tekst = preg_replace('/\s+/', ' ', $tekst);
                // "Continue reading..." verwijderen
                $tekst = preg_replace('/Continue reading\.?\.?\.?\s*$/i', '', $tekst);
                // "The post ... appeared first on ..." en "The post ... first appeared on ..." verwijderen (o.a. Mediaite)
                $tekst = preg_replace('/\s*The post .+? (?:appeared first|first appeared) on .+$/i', '', $tekst);
                // "Like Blaze News? ... Sign up here!" verwijderen (TheBlaze)
                $tekst = preg_replace('/\s*Like Blaze News\?.*$/i', '', $tekst);
                // "Our Standards: The GB News Editorial Charter" verwijderen (GB News)
                $tekst = preg_replace('/\s*Our Standards: The GB News Editorial Charter\s*$/i', '', $tekst);
                // "Read More: https://..." verwijderen (Punch)
                $tekst = preg_replace('/\s*Read More:\s*https?:\/\/\S+\s*$/i', '', $tekst);
                // "[…]" of "[...]" aan het einde van WordPress-samenvattingen vervangen door "…"
                $tekst = preg_replace('/\s*\[(?:…|\.\.\.)\]\s*$/u', '…', $tekst);
                // spaties aan het begin en einde verwijderen
                $tekst = trim($tekst);

                $afbeelding = $this->afbeeldingMetadata($item);
                $bestaandArtikel = Artikel::where('url', $url)->first();

                if ($bestaandArtikel !== null) {
                    if ($bestaandArtikel->afbeelding_url === null && $afbeelding['url'] !== null) {
                        $bestaandArtikel->update($afbeelding);
                    }

                    continue;
                }

                $nieuweArtikelen[] = Artikel::create([ // maak een nieuw artikel aan
                    'titel' => $titel,
                    'publicatiedatum' => $publicatiedatum,
                    'volledige_tekst' => $tekst,
                    'url' => $url,
                    'afbeelding_url' => $afbeelding['url'],
                    'afbeelding_credit' => $afbeelding['credit'],
                    'bron_id' => $bron->id,
                ]);
            }

            // embeddings voor alle nieuwe artikelen van deze bron in één aanroep naar Ollama
            if (config('services.matcher.methode') === 'embeddings') {
                $matcher->maakEmbeddings($nieuweArtikelen);
            }

            $opgehaald = 0;
            $nieuw = 0;
            $bestaand = 0;

            foreach ($nieuweArtikelen as $artikel) {
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

        return self::SUCCESS;
    }

    /**
     * @return array{url: ?string, credit: ?string}
     */
    private function afbeeldingMetadata(SimpleXMLElement $item): array
    {
        $kandidaten = [];

        foreach ($item->enclosure as $enclosure) {
            $attributen = $enclosure->attributes();
            $kandidaten[] = [
                'url' => (string) ($attributen['url'] ?? ''),
                'type' => (string) ($attributen['type'] ?? ''),
                'medium' => '',
            ];
        }

        $namespaces = $item->getNamespaces(true);
        $mediaNamespace = $namespaces['media'] ?? 'http://search.yahoo.com/mrss/';
        $media = $item->children($mediaNamespace);

        foreach (['content', 'thumbnail'] as $elementnaam) {
            foreach ($media->{$elementnaam} as $mediaItem) {
                $attributen = $mediaItem->attributes();
                $kandidaten[] = [
                    'url' => (string) ($attributen['url'] ?? ''),
                    'type' => (string) ($attributen['type'] ?? ''),
                    'medium' => (string) ($attributen['medium'] ?? ''),
                ];
            }
        }

        $credit = isset($media->credit[0]) ? trim((string) $media->credit[0]) : null;

        foreach ($kandidaten as $kandidaat) {
            if ($this->isImageUrl($kandidaat['url'], $kandidaat['type'], $kandidaat['medium'])) {
                return [
                    'url' => $kandidaat['url'],
                    'credit' => $credit !== '' ? $credit : null,
                ];
            }
        }

        return ['url' => null, 'credit' => null];
    }

    private function isImageUrl(string $url, string $type, string $medium): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        $type = strtolower(trim($type));
        $medium = strtolower(trim($medium));

        if (($type !== '' && ! str_starts_with($type, 'image/')) || ($medium !== '' && $medium !== 'image')) {
            return false;
        }

        if (str_starts_with($type, 'image/') || $medium === 'image') {
            return true;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return preg_match('/\.(?:avif|gif|jpe?g|png|webp)$/i', $path) === 1;
    }
}
