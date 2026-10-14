<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArtikelenOrientatieSchattenTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_newest_articles_without_estimate_are_estimated_up_to_the_limit(): void
    {
        $bron = Bron::create(['naam' => 'Testbron', 'orientatie' => 'neutral']);
        $alGeschat = $this->artikel($bron, 'Al geschat', '2026-10-14', ['ai_orientatie' => 'right']);
        $nieuwste = $this->artikel($bron, 'Nieuwste', '2026-10-13');
        $ouder = $this->artikel($bron, 'Ouder', '2026-10-12');
        Http::fake(['*' => $this->aiResponse('left')]);

        $this->artisan('artikelen:orientatie-schatten', ['--limit' => 1])
            ->expectsOutput("{$nieuwste->id}: left (70%) Nieuwste")
            ->assertSuccessful();

        $this->assertSame('left', $nieuwste->fresh()->ai_orientatie);
        $this->assertNull($ouder->fresh()->ai_orientatie);
        $this->assertSame('right', $alGeschat->fresh()->ai_orientatie);
        Http::assertSentCount(1);
    }

    public function test_bron_option_limits_the_estimate_to_that_source(): void
    {
        $gekozen = Bron::create(['naam' => 'Gekozen Bron', 'orientatie' => 'left']);
        $andere = Bron::create(['naam' => 'Andere Bron', 'orientatie' => 'right']);
        $vanGekozen = $this->artikel($gekozen, 'Van gekozen bron', '2026-10-13');
        $vanAndere = $this->artikel($andere, 'Van andere bron', '2026-10-14');
        Http::fake(['*' => $this->aiResponse('neutral')]);

        $this->artisan('artikelen:orientatie-schatten', ['--bron' => 'Gekozen Bron'])->assertSuccessful();

        $this->assertSame('neutral', $vanGekozen->fresh()->ai_orientatie);
        $this->assertNull($vanAndere->fresh()->ai_orientatie);
    }

    public function test_unknown_bron_fails_without_calling_the_ai(): void
    {
        Http::fake();

        $this->artisan('artikelen:orientatie-schatten', ['--bron' => 'Bestaat Niet'])
            ->expectsOutput('Bron Bestaat Niet bestaat niet.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_article_is_skipped_after_three_invalid_answers(): void
    {
        $bron = Bron::create(['naam' => 'Testbron', 'orientatie' => 'neutral']);
        $artikel = $this->artikel($bron, 'Lastig artikel', '2026-10-13');
        Http::fake(['*' => $this->aiResponse('far-left')]);

        $this->artisan('artikelen:orientatie-schatten')
            ->expectsOutput("{$artikel->id}: overgeslagen na 3 pogingen")
            ->assertSuccessful();

        $this->assertNull($artikel->fresh()->ai_orientatie);
        Http::assertSentCount(3);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function artikel(Bron $bron, string $titel, string $datum, array $extra = []): Artikel
    {
        return Artikel::create([
            'titel' => $titel,
            'publicatiedatum' => $datum,
            'volledige_tekst' => 'Tekst over de gebeurtenis.',
            'url' => 'https://example.test/'.urlencode($titel),
            'bron_id' => $bron->id,
            ...$extra,
        ]);
    }

    private function aiResponse(string $orientatie): PromiseInterface
    {
        return Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'orientatie' => $orientatie,
                'zekerheid' => 70,
                'onderbouwing' => 'Zakelijke toon.',
            ])]]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ]);
    }
}
