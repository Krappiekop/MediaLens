<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrientatieVergelijkenTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_per_source_comparison_agreement_per_bucket_and_article_confusion_matrix(): void
    {
        $links = Bron::create(['naam' => 'Linkse Bron', 'orientatie' => 'left-center']);
        $rechts = Bron::create(['naam' => 'Rechtse Bron', 'orientatie' => 'right']);
        $zonderSchatting = Bron::create(['naam' => 'Zonder Schatting', 'orientatie' => 'neutral']);
        $this->artikel($links, ['ai_orientatie' => 'left-center', 'ai_politiek' => true, 'ai_zekerheid' => 60]);
        $this->artikel($links, ['ai_orientatie' => 'left-center', 'ai_politiek' => true, 'ai_zekerheid' => 60]);
        $this->artikel($links, ['ai_orientatie' => 'neutral', 'ai_politiek' => false, 'ai_zekerheid' => 40]);
        $this->artikel($rechts, ['ai_orientatie' => 'left', 'ai_politiek' => true, 'ai_zekerheid' => 80]);
        $this->artikel($zonderSchatting);

        $this->artisan('orientatie:vergelijken')
            ->expectsTable(
                ['Bron', 'MBFC', 'AI', 'Afwijking', 'Artikelen', 'Zekerheid', 'Zelfde bakje'],
                [
                    ['Rechtse Bron', 'right', 'left', '-4.0', 1, '80%', 'nee'],
                    ['Linkse Bron', 'left-center', 'left-center', '+0.0', 2, '60%', 'ja'],
                ],
            )
            ->expectsTable(
                ['MBFC', 'Bronnen', 'Zelfde bakje', 'Overeenstemming', 'Gem. afwijking'],
                [
                    ['Links', 1, 1, '100%', '+0.0'],
                    ['Midden', 0, 0, '-', '-'],
                    ['Rechts', 1, 0, '0%', '-4.0'],
                    ['Totaal', 2, 1, '50%', '-2.0'],
                ],
            )
            ->expectsTable(
                ['MBFC \\ AI', 'Links', 'Midden', 'Rechts', 'Geen lading', 'Totaal'],
                [
                    ['Links', 2, 0, 0, 1, 3],
                    ['Midden', 0, 0, 0, 0, 0],
                    ['Rechts', 1, 0, 0, 0, 1],
                ],
            )
            ->assertSuccessful();
    }

    public function test_min_option_leaves_out_sources_with_too_few_charged_articles(): void
    {
        $genoeg = Bron::create(['naam' => 'Genoeg Artikelen', 'orientatie' => 'neutral']);
        $teWeinig = Bron::create(['naam' => 'Te Weinig Artikelen', 'orientatie' => 'right']);
        $this->artikel($genoeg, ['ai_orientatie' => 'neutral', 'ai_politiek' => true, 'ai_zekerheid' => 50]);
        $this->artikel($genoeg, ['ai_orientatie' => 'neutral', 'ai_politiek' => true, 'ai_zekerheid' => 50]);
        $this->artikel($teWeinig, ['ai_orientatie' => 'left', 'ai_politiek' => true, 'ai_zekerheid' => 90]);

        $this->artisan('orientatie:vergelijken', ['--min' => 2])
            ->expectsTable(
                ['Bron', 'MBFC', 'AI', 'Afwijking', 'Artikelen', 'Zekerheid', 'Zelfde bakje'],
                [['Genoeg Artikelen', 'neutral', 'neutral', '+0.0', 2, '50%', 'ja']],
            )
            ->doesntExpectOutputToContain('Te Weinig Artikelen')
            ->assertSuccessful();
    }

    public function test_reports_when_there_are_no_estimates_to_compare(): void
    {
        $bron = Bron::create(['naam' => 'Testbron', 'orientatie' => 'neutral']);
        $this->artikel($bron);

        $this->artisan('orientatie:vergelijken')
            ->expectsOutput('Geen bronnen met AI-schattingen om te vergelijken.')
            ->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function artikel(Bron $bron, array $extra = []): Artikel
    {
        return Artikel::create([
            'titel' => 'Artikel',
            'publicatiedatum' => '2026-10-13',
            'volledige_tekst' => 'Tekst over de gebeurtenis.',
            'url' => 'https://example.test/'.uniqid(),
            'bron_id' => $bron->id,
            ...$extra,
        ]);
    }
}
