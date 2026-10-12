<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use App\Models\Gebeurtenis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetching_a_feed_saves_media_images_and_skips_audio_enclosures(): void
    {
        Http::preventStrayRequests();

        $bron = Bron::create([
            'naam' => 'Testnieuws',
            'orientatie' => 'left-center',
        ]);
        $bron->feed_url = 'https://feeds.example.test/news.xml';
        $bron->save();

        $feed = <<<'XML'
            <?xml version="1.0"?>
            <rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
                <channel>
                    <item>
                        <title>Stadsraad bespreekt nieuw plan</title>
                        <link>https://news.example.test/stadsraad-plan</link>
                        <pubDate>Mon, 12 Oct 2026 08:00:00 GMT</pubDate>
                        <description>De stadsraad bespreekt vandaag het nieuwe plan voor de buurt.</description>
                        <enclosure url="https://news.example.test/audio.mp3" type="audio/mpeg" />
                        <media:content url="https://images.example.test/stadsraad.jpg" medium="image" type="image/jpeg" />
                        <media:credit>Redactiebeeld</media:credit>
                    </item>
                    <item>
                        <title>Gemeente publiceert vervoersplan</title>
                        <link>https://news.example.test/vervoersplan</link>
                        <pubDate>Mon, 12 Oct 2026 07:00:00 GMT</pubDate>
                        <description>De gemeente publiceert een nieuw vervoersplan voor de regio.</description>
                        <enclosure url="https://news.example.test/report.mp3" type="audio/mpeg" />
                    </item>
                </channel>
            </rss>
            XML;

        Http::fake([
            'https://feeds.example.test/news.xml' => Http::response($feed),
        ]);

        $this->artisan('artikelen:ophalen')->assertSuccessful();

        $artikelMetAfbeelding = Artikel::where('url', 'https://news.example.test/stadsraad-plan')->firstOrFail();
        $this->assertSame('https://images.example.test/stadsraad.jpg', $artikelMetAfbeelding->afbeelding_url);
        $this->assertSame('Redactiebeeld', $artikelMetAfbeelding->afbeelding_credit);

        $artikelZonderAfbeelding = Artikel::where('url', 'https://news.example.test/vervoersplan')->firstOrFail();
        $this->assertNull($artikelZonderAfbeelding->afbeelding_url);
    }

    public function test_homepage_and_detail_page_render_image_cards_and_fallbacks(): void
    {
        $linkerBron = Bron::create(['naam' => 'Bron Links', 'orientatie' => 'left']);
        $rechterBron = Bron::create(['naam' => 'Bron Rechts', 'orientatie' => 'right']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Nieuwe plannen voor de stad']);

        Artikel::create([
            'titel' => 'Bron toont de plannen in beeld',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Een korte tekst over de plannen.',
            'url' => 'https://news.example.test/plan-met-foto',
            'afbeelding_url' => 'https://images.example.test/stadsplan.jpg',
            'afbeelding_credit' => 'Fotograaf Voorbeeld',
            'bron_id' => $linkerBron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        Artikel::create([
            'titel' => 'Een tweede bron bespreekt de plannen',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Een andere korte tekst over de plannen.',
            'url' => 'https://news.example.test/plan-zonder-foto',
            'bron_id' => $rechterBron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Nieuwe plannen voor de stad')
            ->assertSee('w-fit max-w-full', false)
            ->assertSee('https://images.example.test/stadsplan.jpg', false)
            ->assertSee('Beeld: Fotograaf Voorbeeld');

        $this->get(route('gebeurtenissen.show', $gebeurtenis))
            ->assertOk()
            ->assertSee('src="https://news.example.test/favicon.ico"', false)
            ->assertDontSee('https://images.example.test/stadsplan.jpg', false)
            ->assertDontSee('Bekijk onbekende bronnen')
            ->assertSee('loading="lazy"', false)
            ->assertSee('alt="" aria-hidden="true"', false);
    }

    public function test_unknown_sources_are_hidden_in_a_collapsible_section(): void
    {
        $unknownBron = Bron::create(['naam' => 'Onbekende bron', 'orientatie' => 'unclassified']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis met onbekende bron']);

        Artikel::create([
            'titel' => 'Bericht van een onbekende bron',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Een korte tekst.',
            'url' => 'https://unknown.example.test/bericht',
            'bron_id' => $unknownBron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        $this->get(route('gebeurtenissen.show', $gebeurtenis))
            ->assertOk()
            ->assertSee('<details', false)
            ->assertSee('<summary', false)
            ->assertSee('Bekijk onbekende bronnen (1)')
            ->assertSee('Bericht van een onbekende bron')
            ->assertSee('Onbekende bron');
    }
}
