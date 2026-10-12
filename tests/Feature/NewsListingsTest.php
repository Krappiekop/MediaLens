<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use App\Models\Gebeurtenis;
use App\Models\Samenvatting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_can_be_searched_and_filtered_by_perspective(): void
    {
        $leftBron = Bron::create(['naam' => 'Linker nieuwsbron', 'orientatie' => 'left']);
        $rightBron = Bron::create(['naam' => 'Rechter nieuwsbron', 'orientatie' => 'right']);
        $centerBron = Bron::create(['naam' => 'Nieuwsbron uit het midden', 'orientatie' => 'neutral']);

        $eventWithLeftCoverage = Gebeurtenis::create(['onderwerp' => 'Begroting krijgt steun in het parlement']);
        $eventWithoutLeftCoverage = Gebeurtenis::create(['onderwerp' => 'Begroting leidt tot debat in de raad']);
        $eventWithSummary = Gebeurtenis::create(['onderwerp' => 'Begroting krijgt akkoord van partijen']);

        Artikel::create([
            'titel' => 'Begroting krijgt steun',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'De begroting kreeg steun.',
            'url' => 'https://left.example.test/begroting',
            'bron_id' => $leftBron->id,
            'gebeurtenis_id' => $eventWithLeftCoverage->id,
        ]);
        Artikel::create([
            'titel' => 'Kritiek op de begroting',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Er klinkt kritiek.',
            'url' => 'https://right.example.test/begroting-kritiek',
            'bron_id' => $rightBron->id,
            'gebeurtenis_id' => $eventWithLeftCoverage->id,
        ]);
        Artikel::create([
            'titel' => 'Begroting leidt tot debat',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'De raad debatteert.',
            'url' => 'https://right.example.test/begroting-debat',
            'bron_id' => $rightBron->id,
            'gebeurtenis_id' => $eventWithoutLeftCoverage->id,
        ]);
        Artikel::create([
            'titel' => 'Middenbericht over de begroting',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Een tweede bericht.',
            'url' => 'https://center.example.test/begroting',
            'bron_id' => $centerBron->id,
            'gebeurtenis_id' => $eventWithoutLeftCoverage->id,
        ]);
        Artikel::create([
            'titel' => 'Begroting krijgt steun van partijen',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Partijen zijn akkoord.',
            'url' => 'https://left.example.test/begroting-akkoord',
            'bron_id' => $leftBron->id,
            'gebeurtenis_id' => $eventWithSummary->id,
        ]);
        Artikel::create([
            'titel' => 'Reacties op het begrotingsakkoord',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Er volgen reacties.',
            'url' => 'https://right.example.test/begroting-akkoord',
            'bron_id' => $rightBron->id,
            'gebeurtenis_id' => $eventWithSummary->id,
        ]);
        Samenvatting::create([
            'kernfeiten' => 'De partijen bereikten een akkoord.',
            'betrokkenen' => 'Partijen',
            'overeenstemming' => 'Beide bronnen noemen het akkoord.',
            'verschil' => 'De nadruk verschilt.',
            'gebeurtenis_id' => $eventWithSummary->id,
        ]);

        $response = $this->get('/gebeurtenissen?q=begroting&filter=zonder&perspectief=links');

        $response->assertOk()
            ->assertSee('Begroting krijgt steun in het parlement')
            ->assertDontSee('Begroting leidt tot debat in de raad')
            ->assertDontSee('Begroting krijgt akkoord van partijen')
            ->assertSee('value="begroting"', false)
            ->assertSee('name="perspectief"', false);
    }

    public function test_articles_page_includes_unassigned_articles_and_filters_them(): void
    {
        $bron = Bron::create(['naam' => 'Lokaal nieuws', 'orientatie' => 'left-center']);
        $event = Gebeurtenis::create(['onderwerp' => 'Vervoersplan besproken']);

        Artikel::create([
            'titel' => 'Artikel gekoppeld aan een gebeurtenis',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Een gekoppeld bericht.',
            'url' => 'https://news.example.test/gekoppeld',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $event->id,
        ]);
        Artikel::create([
            'titel' => 'Los bericht over lokaal vervoer',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Een bericht dat nog niet bij een gebeurtenis hoort.',
            'url' => 'https://news.example.test/los-vervoer',
            'afbeelding_url' => 'https://images.example.test/vervoer.jpg',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => null,
        ]);

        $this->get('/artikelen')
            ->assertOk()
            ->assertSee('Artikel gekoppeld aan een gebeurtenis')
            ->assertSee('Los bericht over lokaal vervoer');

        $response = $this->get('/artikelen?q=lokaal&status=los&perspectief=links');

        $response->assertOk()
            ->assertSee('Los bericht over lokaal vervoer')
            ->assertSee('https://images.example.test/vervoer.jpg', false)
            ->assertSee('Niet aan een gebeurtenis gekoppeld')
            ->assertDontSee('Artikel gekoppeld aan een gebeurtenis')
            ->assertSee('name="status"', false)
            ->assertSee('name="perspectief"', false);
    }
}
