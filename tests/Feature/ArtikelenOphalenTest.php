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
}
