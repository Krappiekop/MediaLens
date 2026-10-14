<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use App\Models\Gebeurtenis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BronnenTest extends TestCase
{
    use RefreshDatabase;

    public function test_sources_overview_groups_sources_by_perspective_with_reliability(): void
    {
        Bron::create(['naam' => 'Linkse krant', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high']);
        Bron::create(['naam' => 'Rechtse zender', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed']);
        Bron::create(['naam' => 'Bron buiten de dataset', 'orientatie' => 'neutral', 'betrouwbaarheid' => null]);

        $this->get(route('bronnen.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Links', 'Linkse krant', 'betrouwbaarheid hoog',
                'Midden', 'Bron buiten de dataset', 'betrouwbaarheid onbekend',
                'Rechts', 'Rechtse zender', 'betrouwbaarheid gemengd',
            ]);
    }

    public function test_source_page_shows_label_reliability_event_count_and_latest_articles(): void
    {
        $bron = Bron::create(['naam' => 'Testkrant', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'low']);
        $andereBron = Bron::create(['naam' => 'Andere krant', 'orientatie' => 'left']);

        $zichtbaar = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis met twee bronnen']);
        $this->artikel($bron, $zichtbaar, 'Oud artikel van de testkrant', '2026-10-10 09:00:00');
        $this->artikel($andereBron, $zichtbaar, 'Artikel van een andere krant', '2026-10-10 10:00:00');

        $los = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis met één artikel']);
        $this->artikel($bron, $los, 'Nieuw artikel van de testkrant', '2026-10-12 09:00:00');

        $response = $this->get(route('bronnen.show', $bron));

        $response->assertOk()
            ->assertSeeInOrder(['Testkrant', 'Rechts', 'right-center', 'Laag', 'Artikelen', '2', 'Gebeurtenissen', '1'])
            ->assertSeeInOrder(['Nieuw artikel van de testkrant', 'Oud artikel van de testkrant'])
            ->assertDontSee('Artikel van een andere krant')
            ->assertViewHas('aantalGebeurtenissen', 1);
    }

    public function test_event_detail_links_the_source_and_shows_the_article_description_in_a_new_tab(): void
    {
        $bron = Bron::create(['naam' => 'Testkrant', 'orientatie' => 'neutral']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis met omschrijving']);
        $this->artikel($bron, $gebeurtenis, 'Artikel met omschrijving', '2026-10-10 09:00:00');

        $this->get(route('gebeurtenissen.show', $gebeurtenis))
            ->assertOk()
            ->assertSee('href="'.route('bronnen.show', $bron).'"', false)
            ->assertSee('href="https://example.test/Artikel met omschrijving" target="_blank" rel="noopener noreferrer"', false)
            ->assertSee('Omschrijving van Artikel met omschrijving.');
    }

    public function test_article_with_a_non_http_url_is_shown_without_a_link(): void
    {
        $bron = Bron::create(['naam' => 'Testkrant', 'orientatie' => 'neutral']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis met verdachte link']);
        Artikel::create([
            'titel' => 'Artikel met javascript-link',
            'publicatiedatum' => '2026-10-10 09:00:00',
            'volledige_tekst' => 'Tekst.',
            'url' => 'javascript:alert(document.cookie)',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        foreach ([route('gebeurtenissen.show', $gebeurtenis), route('bronnen.show', $bron), route('artikelen.index')] as $pagina) {
            $this->get($pagina)
                ->assertOk()
                ->assertSee('Artikel met javascript-link')
                ->assertDontSee('javascript:alert', false);
        }
    }

    /**
     * Preview: de nieuwste 3 per kolom, de oudere achter "Toon alles".
     */
    public function test_event_detail_shows_three_articles_per_perspective_and_puts_the_rest_behind_show_all(): void
    {
        $links = Bron::create(['naam' => 'Linkse krant', 'orientatie' => 'left']);
        $rechts = Bron::create(['naam' => 'Rechtse krant', 'orientatie' => 'right']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis met veel artikelen']);

        foreach (range(1, 5) as $nummer) {
            $this->artikel($links, $gebeurtenis, "Links {$nummer}", "2026-10-10 0{$nummer}:00:00");
        }
        $this->artikel($rechts, $gebeurtenis, 'Rechts 1', '2026-10-10 09:00:00');

        $this->get(route('gebeurtenissen.show', $gebeurtenis))
            ->assertOk()
            ->assertSeeInOrder(['Links 5', 'Links 4', 'Links 3', 'data-extra-artikelen', 'Links 2', 'Links 1', 'Rechts 1'], false)
            ->assertSee('aria-controls="meer-links"', false)
            ->assertSee('data-tekst-meer="Toon alle 6 artikelen"', false);
    }

    public function test_event_detail_has_no_show_all_button_when_every_perspective_fits_in_the_preview(): void
    {
        $bron = Bron::create(['naam' => 'Testkrant', 'orientatie' => 'neutral']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Kleine gebeurtenis']);

        foreach (range(1, 3) as $nummer) {
            $this->artikel($bron, $gebeurtenis, "Midden {$nummer}", "2026-10-10 0{$nummer}:00:00");
        }

        $this->get(route('gebeurtenissen.show', $gebeurtenis))
            ->assertOk()
            ->assertDontSee('data-extra-artikelen', false)
            ->assertDontSee('data-toon-alles', false);
    }

    public function test_source_page_shows_ai_estimate_weighted_by_certainty_without_articles_lacking_political_charge(): void
    {
        $bron = Bron::create(['naam' => 'Testkrant', 'orientatie' => 'right']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis']);
        $this->artikel($bron, $gebeurtenis, 'Links artikel', '2026-10-10 09:00:00', ['ai_orientatie' => 'left', 'ai_politiek' => true, 'ai_zekerheid' => 60]);
        $this->artikel($bron, $gebeurtenis, 'Neutraal artikel', '2026-10-10 10:00:00', ['ai_orientatie' => 'neutral', 'ai_politiek' => true, 'ai_zekerheid' => 40]);
        $this->artikel($bron, $gebeurtenis, 'Sportartikel', '2026-10-10 11:00:00', ['ai_orientatie' => 'right', 'ai_politiek' => false, 'ai_zekerheid' => 90]);
        $this->artikel($bron, $gebeurtenis, 'Niet geschat', '2026-10-10 12:00:00');

        $this->get(route('bronnen.show', $bron))
            ->assertOk()
            ->assertSeeInOrder([
                'Politiek label (MBFC)', 'Rechts', 'right',
                'AI-schatting', 'Links', 'left-center · score -1,2', '2 politieke artikelen, gem. zekerheid 50%', 'Wijkt af van MBFC',
            ]);
    }

    public function test_source_page_without_estimated_political_articles_shows_no_ai_estimate_yet(): void
    {
        $bron = Bron::create(['naam' => 'Testkrant', 'orientatie' => 'neutral']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis']);
        $this->artikel($bron, $gebeurtenis, 'Sportartikel', '2026-10-10 11:00:00', ['ai_orientatie' => 'neutral', 'ai_politiek' => false, 'ai_zekerheid' => 30]);

        $this->get(route('bronnen.show', $bron))
            ->assertOk()
            ->assertSeeInOrder(['AI-schatting', 'Nog geen'])
            ->assertDontSee('Wijkt af van MBFC');
    }

    public function test_event_detail_shows_the_ai_estimate_per_article_next_to_the_mbfc_label(): void
    {
        $bron = Bron::create(['naam' => 'Testkrant', 'orientatie' => 'right-center']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Gebeurtenis']);
        $this->artikel($bron, $gebeurtenis, 'Geschat artikel', '2026-10-10 09:00:00', [
            'ai_orientatie' => 'left-center', 'ai_politiek' => true, 'ai_zekerheid' => 65, 'ai_onderbouwing' => 'Citeert alleen de oppositie.',
        ]);
        $this->artikel($bron, $gebeurtenis, 'Sportartikel', '2026-10-10 10:00:00', [
            'ai_orientatie' => 'neutral', 'ai_politiek' => false, 'ai_zekerheid' => 30, 'ai_onderbouwing' => 'Wedstrijdverslag.',
        ]);
        $this->artikel($bron, $gebeurtenis, 'Niet geschat artikel', '2026-10-10 08:00:00');

        $response = $this->get(route('gebeurtenissen.show', $gebeurtenis));

        $response->assertOk()
            ->assertSee('MBFC: right-center')
            ->assertSeeInOrder(['Sportartikel', 'AI: geen politieke lading · zekerheid 30%', 'Geschat artikel', 'AI: left-center · zekerheid 65%', 'Citeert alleen de oppositie.', 'Niet geschat artikel']);
        $this->assertSame(2, substr_count($response->getContent(), 'AI: '));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function artikel(Bron $bron, Gebeurtenis $gebeurtenis, string $titel, string $tijdstip, array $extra = []): Artikel
    {
        return Artikel::create([
            'titel' => $titel,
            'publicatiedatum' => $tijdstip,
            'volledige_tekst' => "Omschrijving van {$titel}.",
            'url' => "https://example.test/{$titel}",
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
            ...$extra,
        ]);
    }
}
