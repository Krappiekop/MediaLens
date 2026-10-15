<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use App\Models\Gebeurtenis;
use App\Services\ArtikelVergelijker;
use App\Services\LiteLlm;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LiteLlmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.litellm.base_url' => 'https://proxy.test',
            'services.litellm.api_key' => 'test-sleutel',
            'services.litellm.model' => 'openrouter/deepseek/deepseek-v4-flash',
        ]);
    }

    public function test_request_carries_a_session_id_per_type_and_hour_without_deepseek_parameters(): void
    {
        $this->travelTo('2026-10-15 09:41:00');
        Http::fake(['proxy.test/*' => $this->antwoord()]);

        (new LiteLlm)->vraag('systeem', 'bericht', soort: 'orientatie');

        Http::assertSent(fn (Request $request) => $request['session_id'] === 'medialens-orientatie-2026101509'
            && ! isset($request['thinking'])
            && ! isset($request['reasoning_effort']));
    }

    /**
     * @param  array<string, mixed>|null  $verwacht
     */
    #[DataProvider('reasoningInstellingen')]
    public function test_reasoning_setting_is_sent_as_the_openrouter_parameter(string $instelling, ?array $verwacht): void
    {
        config(['services.litellm.reasoning.orientatie' => $instelling]);
        Http::fake(['proxy.test/*' => $this->antwoord()]);

        (new LiteLlm)->vraag('systeem', 'bericht', soort: 'orientatie');

        Http::assertSent(fn (Request $request) => ($request->data()['reasoning'] ?? null) === $verwacht);
    }

    /**
     * @return array<string, array{string, array<string, mixed>|null}>
     */
    public static function reasoningInstellingen(): array
    {
        return [
            'leeg: standaard van de provider' => ['', null],
            'uit' => ['uit', ['enabled' => false]],
            'effort' => ['low', ['effort' => 'low']],
        ];
    }

    public function test_reasoning_setting_of_the_type_wins_over_the_default(): void
    {
        config(['services.litellm.reasoning' => ['standaard' => 'high', 'categorie' => 'uit']]);
        Http::fake(['proxy.test/*' => $this->antwoord()]);

        (new LiteLlm)->vraag('systeem', 'bericht', soort: 'categorie');
        (new LiteLlm)->vraag('systeem', 'bericht', soort: 'onbekend');

        Http::assertSentInOrder([
            fn (Request $request) => $request['reasoning'] === ['enabled' => false],
            fn (Request $request) => $request['reasoning'] === ['effort' => 'high'],
        ]);
    }

    public function test_usage_is_logged_with_provider_cache_reasoning_and_the_cost_from_openrouter(): void
    {
        Http::fake(['proxy.test/*' => $this->antwoord([
            'prompt_tokens' => 532,
            'completion_tokens' => 244,
            'prompt_tokens_details' => ['cached_tokens' => 441, 'cache_write_tokens' => 0],
            'completion_tokens_details' => ['reasoning_tokens' => 241],
            'cost' => 0.0000607152,
        ], 'GMICloud')]);
        Log::shouldReceive('info')->once()->with('llm usage', Mockery::on(fn (array $context) => $context['soort'] === 'samenvatting'
            && $context['gebeurtenis_id'] === 38
            && $context['provider'] === 'GMICloud'
            && $context['prompt_tokens'] === 532
            && $context['cached_tokens'] === 441
            && $context['reasoning_tokens'] === 241
            && $context['kosten_totaal'] === 0.000061
            && $context['kosten_bron'] === 'openrouter'
            && is_int($context['duur_ms'])));

        (new LiteLlm)->vraag('systeem', 'bericht', gebeurtenisId: 38, soort: 'samenvatting');
    }

    public function test_cost_is_estimated_from_the_env_prices_when_openrouter_sends_no_cost(): void
    {
        config([
            'services.litellm.price_input' => 1.0,
            'services.litellm.price_cached' => 0.1,
            'services.litellm.price_output' => 2.0,
        ]);
        Http::fake(['proxy.test/*' => $this->antwoord([
            'prompt_tokens' => 1_000_000,
            'completion_tokens' => 1_000_000,
            'prompt_tokens_details' => ['cached_tokens' => 500_000],
        ])]);
        Log::shouldReceive('info')->once()->with('llm usage', Mockery::on(fn (array $context) => $context['kosten_totaal'] === 2.55
            && $context['kosten_bron'] === 'schatting'));

        (new LiteLlm)->vraag('systeem', 'bericht');
    }

    public function test_a_new_article_is_appended_so_the_earlier_prompt_stays_the_same(): void
    {
        $bron = Bron::create(['naam' => 'Testbron', 'orientatie' => 'neutral']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Begroting']);
        $this->artikel($bron, $gebeurtenis, 'Eerste artikel');
        $this->artikel($bron, $gebeurtenis, 'Tweede artikel');
        $vergelijker = new ArtikelVergelijker;

        $voor = $vergelijker->artikelenTekst($gebeurtenis);
        $this->artikel($bron, $gebeurtenis, 'Derde artikel');
        $na = $vergelijker->artikelenTekst($gebeurtenis);

        $this->assertStringStartsWith($voor, $na);
        $this->assertStringEndsWith('Tekst: Derde artikel', $na);
    }

    private function artikel(Bron $bron, Gebeurtenis $gebeurtenis, string $titel): Artikel
    {
        return Artikel::create([
            'titel' => $titel,
            'publicatiedatum' => '2026-10-15 08:00:00',
            'volledige_tekst' => $titel,
            'url' => 'https://example.test/'.str($titel)->slug(),
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $usage
     */
    private function antwoord(array $usage = ['prompt_tokens' => 10, 'completion_tokens' => 5], ?string $provider = null): PromiseInterface
    {
        return Http::response([
            'choices' => [['message' => ['content' => '{"ok": true}']]],
            'usage' => $usage,
            'provider' => $provider,
        ]);
    }
}
