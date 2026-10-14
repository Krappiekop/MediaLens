<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use App\Services\OrientatieSchatter;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrientatieSchatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_is_stored_on_the_article_with_model_and_time(): void
    {
        $this->travelTo('2026-10-14 16:00:00');
        config(['services.litellm.model' => 'deepseek-test']);
        $artikel = $this->artikel();
        Http::fake(['*' => $this->aiResponse([
            'orientatie' => 'left-center',
            'zekerheid' => 65,
            'onderbouwing' => '  Het artikel noemt de maatregel "wreed".  ',
        ])]);

        (new OrientatieSchatter)->slaOp($artikel);

        $artikel->refresh();
        $this->assertSame('left-center', $artikel->ai_orientatie);
        $this->assertSame(65, $artikel->ai_zekerheid);
        $this->assertSame('Het artikel noemt de maatregel "wreed".', $artikel->ai_onderbouwing);
        $this->assertSame('deepseek-test', $artikel->ai_model);
        $this->assertSame('2026-10-14 16:00:00', $artikel->ai_geschat_op->format('Y-m-d H:i:s'));
    }

    public function test_prompt_contains_the_article_but_not_the_source_name_or_mbfc_label(): void
    {
        $artikel = $this->artikel();
        Http::fake(['*' => $this->aiResponse([
            'orientatie' => 'neutral',
            'zekerheid' => 40,
            'onderbouwing' => 'Zakelijke toon.',
        ])]);

        (new OrientatieSchatter)->schat($artikel);

        Http::assertSent(function ($request) {
            $bericht = $request['messages'][1]['content'];

            return str_contains($bericht, 'Senaat stemt over begroting')
                && str_contains($bericht, 'De senaat stemt vandaag.')
                && ! str_contains($bericht, 'Rechtse Krant')
                && ! str_contains($bericht, 'right-center');
        });
    }

    /**
     * @param  array<string, mixed>  $antwoord
     */
    #[DataProvider('ongeldigeAntwoorden')]
    public function test_invalid_model_answer_throws_and_stores_nothing(array $antwoord, string $melding): void
    {
        $artikel = $this->artikel();
        Http::fake(['*' => $this->aiResponse($antwoord)]);

        try {
            (new OrientatieSchatter)->slaOp($artikel);
            $this->fail('Er had een exception moeten komen.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString($melding, $exception->getMessage());
        }

        $this->assertNull($artikel->fresh()->ai_orientatie);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function ongeldigeAntwoorden(): array
    {
        return [
            'onbekend label' => [['orientatie' => 'far-left', 'zekerheid' => 50, 'onderbouwing' => 'Tekst.'], 'veld orientatie'],
            'zekerheid boven 100' => [['orientatie' => 'left', 'zekerheid' => 150, 'onderbouwing' => 'Tekst.'], 'veld zekerheid'],
            'zekerheid als tekst' => [['orientatie' => 'left', 'zekerheid' => 'hoog', 'onderbouwing' => 'Tekst.'], 'veld zekerheid'],
            'lege onderbouwing' => [['orientatie' => 'left', 'zekerheid' => 50, 'onderbouwing' => '  '], 'veld onderbouwing'],
        ];
    }

    private function artikel(): Artikel
    {
        $bron = Bron::create(['naam' => 'Rechtse Krant', 'orientatie' => 'right-center']);

        return Artikel::create([
            'titel' => 'Senaat stemt over begroting',
            'publicatiedatum' => '2026-10-14',
            'volledige_tekst' => '<p>De senaat stemt vandaag.</p>',
            'url' => 'https://example.test/begroting',
            'bron_id' => $bron->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $inhoud
     */
    private function aiResponse(array $inhoud): PromiseInterface
    {
        return Http::response([
            'choices' => [['message' => ['content' => json_encode($inhoud)]]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ]);
    }
}
