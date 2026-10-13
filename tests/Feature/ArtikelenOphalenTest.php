<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArtikelenOphalenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function boilerplateProvider(): array
    {
        return [
            'Mediaite "first appeared on"' => [
                'De senator reageerde fel op de kritiek. The post Senator reageert fel first appeared on Mediaite .',
                'De senator reageerde fel op de kritiek.',
            ],
            'WordPress "appeared first on"' => [
                'De senator reageerde fel op de kritiek. The post Senator reageert fel appeared first on Voorbeeldsite.',
                'De senator reageerde fel op de kritiek.',
            ],
            'TheBlaze nieuwsbriefoproep' => [
                'De senator reageerde fel op de kritiek. Like Blaze News? Bypass the censors, sign up for our newsletters, and get stories like this direct to your inbox. <a href="https://www.theblaze.com/newsletters">Sign up here</a>!',
                'De senator reageerde fel op de kritiek.',
            ],
            'GB News redactiestatuut' => [
                'De senator reageerde fel op de kritiek. <p>Our Standards: <a href="https://www.gbnews.com/editorial-charter">The GB News Editorial Charter</a></p>',
                'De senator reageerde fel op de kritiek.',
            ],
            'Punch "Read More"-link' => [
                'De senator reageerde fel op de kritiek. Read More: https://punchng.com/senator-reageert-fel/',
                'De senator reageerde fel op de kritiek.',
            ],
            'WordPress afgekapte samenvatting' => [
                'De senator reageerde fel op de kritiek en zei dat [&#8230;]',
                'De senator reageerde fel op de kritiek en zei dat…',
            ],
        ];
    }

    #[DataProvider('boilerplateProvider')]
    public function test_fetching_a_feed_strips_repeated_boilerplate_from_the_article_text(string $description, string $verwachteTekst): void
    {
        Http::preventStrayRequests();

        $bron = Bron::create([
            'naam' => 'Testnieuws',
            'orientatie' => 'neutral',
        ]);
        $bron->feed_url = 'https://feeds.example.test/news.xml';
        $bron->save();

        $feed = '<?xml version="1.0"?><rss version="2.0"><channel><item>'
            .'<title>Senator reageert fel</title>'
            .'<link>https://news.example.test/senator</link>'
            .'<pubDate>Mon, 12 Oct 2026 08:00:00 GMT</pubDate>'
            .'<description>'.htmlspecialchars($description, ENT_XML1).'</description>'
            .'</item></channel></rss>';

        Http::fake([
            'https://feeds.example.test/news.xml' => Http::response($feed),
        ]);

        $this->artisan('artikelen:ophalen')->assertSuccessful();

        $artikel = Artikel::where('url', 'https://news.example.test/senator')->firstOrFail();
        $this->assertSame($verwachteTekst, $artikel->volledige_tekst);
    }

    public function test_fetching_a_feed_keeps_read_more_text_that_is_not_a_trailing_link(): void
    {
        Http::preventStrayRequests();

        $bron = Bron::create([
            'naam' => 'Testnieuws',
            'orientatie' => 'neutral',
        ]);
        $bron->feed_url = 'https://feeds.example.test/news.xml';
        $bron->save();

        $feed = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0">
                <channel>
                    <item>
                        <title>Bibliotheek start leesactie</title>
                        <link>https://news.example.test/leesactie</link>
                        <pubDate>Mon, 12 Oct 2026 08:00:00 GMT</pubDate>
                        <description>The campaign is called Read More: Books for Everyone and starts today.</description>
                    </item>
                </channel>
            </rss>
            XML;

        Http::fake([
            'https://feeds.example.test/news.xml' => Http::response($feed),
        ]);

        $this->artisan('artikelen:ophalen')->assertSuccessful();

        $artikel = Artikel::where('url', 'https://news.example.test/leesactie')->firstOrFail();
        $this->assertSame('The campaign is called Read More: Books for Everyone and starts today.', $artikel->volledige_tekst);
    }

    public function test_fetching_a_feed_links_differently_worded_articles_via_ollama_embeddings(): void
    {
        Http::preventStrayRequests();
        config(['services.matcher.methode' => 'embeddings', 'services.matcher.minimale_similarity' => 0.8]);

        $bron = Bron::create([
            'naam' => 'Testnieuws',
            'orientatie' => 'neutral',
        ]);
        $bron->feed_url = 'https://feeds.example.test/news.xml';
        $bron->save();

        $feed = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0">
                <channel>
                    <item>
                        <title>Israel strikes Gaza</title>
                        <link>https://news.example.test/gaza-1</link>
                        <pubDate>Mon, 12 Oct 2026 08:00:00 GMT</pubDate>
                        <description>Airstrikes hit the city overnight.</description>
                    </item>
                    <item>
                        <title>IDF bombardment in Palestinian enclave</title>
                        <link>https://news.example.test/gaza-2</link>
                        <pubDate>Mon, 12 Oct 2026 09:00:00 GMT</pubDate>
                        <description>Explosions were reported across the territory.</description>
                    </item>
                </channel>
            </rss>
            XML;

        Http::fake([
            'https://feeds.example.test/news.xml' => Http::response($feed),
            'http://ollama.test/api/embed' => Http::response(['embeddings' => [[1.0, 0.0, 0.1], [0.98, 0.05, 0.12]]]),
        ]);
        config(['services.ollama.base_url' => 'http://ollama.test', 'services.ollama.embedding_model' => 'nomic-embed-text']);

        $this->artisan('artikelen:ophalen')->assertSuccessful();

        $eerste = Artikel::where('url', 'https://news.example.test/gaza-1')->firstOrFail();
        $tweede = Artikel::where('url', 'https://news.example.test/gaza-2')->firstOrFail();

        $this->assertEquals([1.0, 0.0, 0.1], $eerste->embedding);
        $this->assertSame($eerste->gebeurtenis_id, $tweede->gebeurtenis_id);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'http://ollama.test/api/embed'
            && $request['model'] === 'nomic-embed-text'
            && $request['input'][0] === 'clustering: Israel strikes Gaza Airstrikes hit the city overnight.');
    }

    public function test_fetching_a_feed_falls_back_to_rake_when_ollama_is_unreachable(): void
    {
        Http::preventStrayRequests();
        config(['services.matcher.methode' => 'embeddings', 'services.ollama.base_url' => 'http://ollama.test']);

        $bron = Bron::create([
            'naam' => 'Testnieuws',
            'orientatie' => 'neutral',
        ]);
        $bron->feed_url = 'https://feeds.example.test/news.xml';
        $bron->save();

        $feed = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0">
                <channel>
                    <item>
                        <title>Senaat stemt over begroting</title>
                        <link>https://news.example.test/begroting</link>
                        <pubDate>Mon, 12 Oct 2026 08:00:00 GMT</pubDate>
                        <description>De senaat stemt vandaag.</description>
                    </item>
                </channel>
            </rss>
            XML;

        Http::fake([
            'https://feeds.example.test/news.xml' => Http::response($feed),
            'http://ollama.test/api/embed' => Http::failedConnection(),
        ]);

        $this->artisan('artikelen:ophalen')->assertSuccessful();

        $artikel = Artikel::where('url', 'https://news.example.test/begroting')->firstOrFail();
        $this->assertNull($artikel->embedding);
        $this->assertNotNull($artikel->gebeurtenis_id);
    }
}
