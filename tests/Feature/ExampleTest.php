<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Bron;
use App\Models\Gebeurtenis;
use App\Services\ArtikelVergelijker;
use App\Services\GebeurtenisMatcher;
use App\Services\LiteLlm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_gebeurtenissen_overview_loads_with_sample_data(): void
    {
        $bronA = Bron::create([
            'naam' => 'Test Bron A',
            'orientatie' => 'left',
        ]);

        $bronB = Bron::create([
            'naam' => 'Test Bron B',
            'orientatie' => 'right',
        ]);

        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => 'Testgebeurtenis met twee artikelen',
        ]);

        Artikel::create([
            'titel' => 'Eerste artikel over de testgebeurtenis',
            'publicatiedatum' => '2026-10-11',
            'volledige_tekst' => 'De gemeente kondigde een nieuwe regeling aan voor de buurt.',
            'url' => 'https://example.com/artikel-1',
            'bron_id' => $bronA->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        Artikel::create([
            'titel' => 'Tweede artikel over dezelfde gebeurtenis',
            'publicatiedatum' => '2026-10-12',
            'volledige_tekst' => 'Politici reageerden op de nieuwe regeling in de buurt.',
            'url' => 'https://example.com/artikel-2',
            'bron_id' => $bronB->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        $response = $this->get('/gebeurtenissen');

        $response->assertOk();
        $response->assertSee('Testgebeurtenis met twee artikelen');
    }

    public function test_litellm_throws_a_clear_exception_when_the_proxy_fails(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'bad gateway',
                ],
            ], 502),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LLM call failed with HTTP 502');

        (new LiteLlm)->vraag('systeem', 'bericht');
    }

    public function test_litellm_requires_a_complete_configuration_before_calling_the_api(): void
    {
        config()->set('services.litellm.base_url', null);
        config()->set('services.litellm.api_key', null);
        config()->set('services.litellm.model', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LLM-configuratie ontbreekt');

        (new LiteLlm)->vraag('systeem', 'bericht');
    }

    public function test_event_detail_page_handles_empty_article_data_without_crashing(): void
    {
        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => 'Gebeurtenis zonder artikelen',
        ]);

        $response = $this->get('/gebeurtenissen/'.$gebeurtenis->id);

        $response->assertOk();
        $response->assertSee('Gebeurtenis zonder artikelen');
    }

    public function test_event_detail_page_handles_articles_without_a_bron_without_crashing(): void
    {
        $bron = Bron::create([
            'naam' => 'Bron voor render-test',
            'orientatie' => 'left',
        ]);

        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => 'Gebeurtenis met artikel zonder bron',
        ]);

        $artikel = Artikel::create([
            'titel' => 'Artikel zonder bron',
            'publicatiedatum' => '2026-10-11',
            'volledige_tekst' => 'Tekst zonder bron',
            'url' => 'https://example.com/zonder-bron',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        $artikel->setRelation('bron', null);
        $gebeurtenis->setRelation('artikelen', collect([$artikel]));

        $html = view('gebeurtenissen.show', [
            'gebeurtenis' => $gebeurtenis,
            'artikelenPerOrientatie' => collect([
                'Links' => collect([$artikel]),
                'Midden' => collect(),
                'Rechts' => collect(),
                'Onbekend' => collect(),
            ]),
        ])->render();

        $this->assertStringContainsString('Gebeurtenis met artikel zonder bron', $html);
        $this->assertStringContainsString('Artikel zonder bron', $html);
        $this->assertStringContainsString('Bron onbekend', $html);
    }

    public function test_samenvatting_rejects_non_string_ai_fields(): void
    {
        $bron = Bron::create([
            'naam' => 'Bron voor validatie',
            'orientatie' => 'left',
        ]);

        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => 'Gebeurtenis met ongeldige AI output',
        ]);

        Artikel::create([
            'titel' => 'Eerste artikel',
            'publicatiedatum' => '2026-10-10',
            'volledige_tekst' => 'Eerste tekst',
            'url' => 'https://example.com/een',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        Artikel::create([
            'titel' => 'Tweede artikel',
            'publicatiedatum' => '2026-10-11',
            'volledige_tekst' => 'Tweede tekst',
            'url' => 'https://example.com/twee',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'kernfeiten' => ['niet', 'string'],
                            'betrokkenen' => 'Minister, burgemeester',
                            'overeenstemming' => 'Beide bronnen noemen dezelfde feiten.',
                            'verschil' => 'De toon verschilt licht.',
                        ]),
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 8,
                ],
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ongeldige JSON van het model');

        (new ArtikelVergelijker)->slaOp($gebeurtenis);
    }

    public function test_articles_are_only_matched_within_the_same_day(): void
    {
        $bron = Bron::create([
            'naam' => 'Bron voor dagvergrendeling',
            'orientatie' => 'left-center',
        ]);

        $gebeurtenis = Gebeurtenis::create([
            'onderwerp' => 'Eerste gebeurtenis',
        ]);

        $eersteArtikel = Artikel::create([
            'titel' => 'Beleid op begroting en verkiezingen',
            'publicatiedatum' => '2026-10-10',
            'volledige_tekst' => 'Regering noemt begroting een akkoord en verkiezingen volgen na overleg over de vakbonden.',
            'url' => 'https://example.com/volgende-dag-1',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        $nieuwArtikel = Artikel::create([
            'titel' => 'Nieuwe groep over begroting en verkiezingen',
            'publicatiedatum' => '2026-10-11',
            'volledige_tekst' => 'Regering noemt begroting een akkoord en verkiezingen volgen na overleg over de vakbonden.',
            'url' => 'https://example.com/volgende-dag-2',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => null,
        ]);

        $resultaat = (new GebeurtenisMatcher)->koppel($nieuwArtikel);

        $this->assertSame('nieuw', $resultaat);
        $this->assertNotNull($nieuwArtikel->fresh()->gebeurtenis_id);
        $this->assertNotSame($gebeurtenis->id, $nieuwArtikel->fresh()->gebeurtenis_id);
    }

    public function test_matcher_koppelt_aan_de_kandidaat_met_de_meeste_overlap(): void
    {
        $bron = Bron::create([
            'naam' => 'Bron voor beste match',
            'orientatie' => 'center',
        ]);

        $zwakkeGebeurtenis = Gebeurtenis::create(['onderwerp' => 'Zwakke match']);
        $sterkeGebeurtenis = Gebeurtenis::create(['onderwerp' => 'Sterke match']);

        Artikel::create([
            'titel' => 'Zwakke kandidaat',
            'publicatiedatum' => '2026-10-10',
            'volledige_tekst' => 'alpha, bravo, charlie, delta, echo, foxtrot, kilo, lima.',
            'url' => 'https://example.com/zwakke-kandidaat',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $zwakkeGebeurtenis->id,
        ]);

        Artikel::create([
            'titel' => 'Sterke kandidaat',
            'publicatiedatum' => '2026-10-10',
            'volledige_tekst' => 'alpha, bravo, charlie, delta, echo, foxtrot, golf, hotel.',
            'url' => 'https://example.com/sterke-kandidaat',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $sterkeGebeurtenis->id,
        ]);

        $nieuwArtikel = Artikel::create([
            'titel' => 'Nieuw artikel',
            'publicatiedatum' => '2026-10-10',
            'volledige_tekst' => 'alpha, bravo, charlie, delta, echo, foxtrot, golf, hotel.',
            'url' => 'https://example.com/nieuw-artikel',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => null,
        ]);

        $resultaat = (new GebeurtenisMatcher)->koppel($nieuwArtikel);

        $this->assertSame('bestaand', $resultaat);
        $this->assertSame($sterkeGebeurtenis->id, $nieuwArtikel->fresh()->gebeurtenis_id);
    }
}
