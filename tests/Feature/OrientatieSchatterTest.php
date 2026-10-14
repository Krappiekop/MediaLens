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
        $artikel = $this->artikel(str_repeat('De senaat stemt vandaag over de begroting. ', 10));
        Http::fake(['*' => $this->aiResponse([
            'politiek_geladen' => true,
            'orientatie' => 'left-center',
            'zekerheid' => 65,
            'onderbouwing' => '  Het artikel noemt de maatregel "wreed".  ',
        ])]);

        (new OrientatieSchatter)->slaOp($artikel);

        $artikel->refresh();
        $this->assertTrue($artikel->ai_politiek);
        $this->assertSame('left-center', $artikel->ai_orientatie);
        $this->assertSame(65, $artikel->ai_zekerheid);
        $this->assertSame('Het artikel noemt de maatregel "wreed".', $artikel->ai_onderbouwing);
        $this->assertSame('deepseek-test', $artikel->ai_model);
        $this->assertSame('2026-10-14 16:00:00', $artikel->ai_geschat_op->format('Y-m-d H:i:s'));
    }

    public function test_article_without_political_charge_becomes_neutral_with_capped_certainty(): void
    {
        $artikel = $this->artikel(str_repeat('Het toernooi begint zaterdag in de sporthal. ', 10));
        Http::fake(['*' => $this->aiResponse([
            'politiek_geladen' => false,
            'orientatie' => 'left',
            'zekerheid' => 95,
            'onderbouwing' => 'Sportverslag zonder politieke inhoud.',
        ])]);

        $schatting = (new OrientatieSchatter)->schat($artikel);

        $this->assertFalse($schatting['politiek_geladen']);
        $this->assertSame('neutral', $schatting['orientatie']);
        $this->assertSame(40, $schatting['zekerheid']);
    }

    public function test_short_political_text_keeps_its_orientation_with_capped_certainty(): void
    {
        $artikel = $this->artikel('De senaat stemt vandaag.');
        Http::fake(['*' => $this->aiResponse([
            'politiek_geladen' => true,
            'orientatie' => 'right',
            'zekerheid' => 85,
            'onderbouwing' => 'Korte tekst.',
        ])]);

        $schatting = (new OrientatieSchatter)->schat($artikel);

        $this->assertSame('right', $schatting['orientatie']);
        $this->assertSame(40, $schatting['zekerheid']);
    }

    public function test_prompt_contains_the_article_but_not_the_source_name_or_mbfc_label(): void
    {
        $artikel = $this->artikel('<p>De senaat stemt vandaag.</p>');
        Http::fake(['*' => $this->aiResponse([
            'politiek_geladen' => true,
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
        $artikel = $this->artikel('De senaat stemt vandaag.');
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
        $geldig = ['politiek_geladen' => true, 'orientatie' => 'left', 'zekerheid' => 50, 'onderbouwing' => 'Tekst.'];

        return [
            'politiek_geladen als tekst' => [[...$geldig, 'politiek_geladen' => 'ja'], 'veld politiek_geladen'],
            'onbekend label' => [[...$geldig, 'orientatie' => 'far-left'], 'veld orientatie'],
            'zekerheid boven 100' => [[...$geldig, 'zekerheid' => 150], 'veld zekerheid'],
            'zekerheid als tekst' => [[...$geldig, 'zekerheid' => 'hoog'], 'veld zekerheid'],
            'lege onderbouwing' => [[...$geldig, 'onderbouwing' => '  '], 'veld onderbouwing'],
        ];
    }

    private function artikel(string $tekst): Artikel
    {
        $bron = Bron::create(['naam' => 'Rechtse Krant', 'orientatie' => 'right-center']);

        return Artikel::create([
            'titel' => 'Senaat stemt over begroting',
            'publicatiedatum' => '2026-10-14',
            'volledige_tekst' => $tekst,
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
