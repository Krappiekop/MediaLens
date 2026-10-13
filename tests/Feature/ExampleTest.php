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

    public function test_articles_are_matched_across_midnight_within_48_hours(): void
    {
        $bron = Bron::create(['naam' => 'Bron voor middernacht', 'orientatie' => 'left-center']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Begroting voor middernacht']);

        Artikel::create([
            'titel' => 'Beleid op begroting en verkiezingen',
            'publicatiedatum' => '2026-10-10 23:30:00',
            'volledige_tekst' => 'Regering noemt begroting een akkoord en verkiezingen volgen na overleg over de vakbonden.',
            'url' => 'https://example.com/voor-middernacht',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        $nieuwArtikel = Artikel::create([
            'titel' => 'Begroting en verkiezingen na middernacht',
            'publicatiedatum' => '2026-10-11 00:30:00',
            'volledige_tekst' => 'Regering noemt begroting een akkoord en verkiezingen volgen na overleg over de vakbonden.',
            'url' => 'https://example.com/na-middernacht',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => null,
        ]);

        $resultaat = (new GebeurtenisMatcher)->koppel($nieuwArtikel);

        $this->assertSame('bestaand', $resultaat);
        $this->assertSame($gebeurtenis->id, $nieuwArtikel->fresh()->gebeurtenis_id);
    }

    public function test_articles_more_than_48_hours_apart_are_not_matched(): void
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
            'publicatiedatum' => '2026-10-10 12:00:00',
            'volledige_tekst' => 'Regering noemt begroting een akkoord en verkiezingen volgen na overleg over de vakbonden.',
            'url' => 'https://example.com/volgende-dag-1',
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenis->id,
        ]);

        $nieuwArtikel = Artikel::create([
            'titel' => 'Nieuwe groep over begroting en verkiezingen',
            'publicatiedatum' => '2026-10-12 12:01:00',
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

    public function test_matcher_koppelt_met_embeddings_aan_de_meest_gelijkende_kandidaat(): void
    {
        config(['services.matcher.methode' => 'embeddings', 'services.matcher.minimale_similarity' => 0.8]);

        $bron = Bron::create(['naam' => 'Bron voor embeddings', 'orientatie' => 'center']);
        $redelijkeGebeurtenis = Gebeurtenis::create(['onderwerp' => 'Redelijke match']);
        $besteGebeurtenis = Gebeurtenis::create(['onderwerp' => 'Beste match']);

        $this->artikelMetEmbedding($bron, 'redelijk', [0.8, 0.6, 0.0], $redelijkeGebeurtenis->id);
        $this->artikelMetEmbedding($bron, 'beste', [0.99, 0.1, 0.0], $besteGebeurtenis->id);
        $nieuwArtikel = $this->artikelMetEmbedding($bron, 'nieuw', [1.0, 0.0, 0.0], null);

        $resultaat = (new GebeurtenisMatcher)->koppel($nieuwArtikel);

        $this->assertSame('bestaand', $resultaat);
        $this->assertSame($besteGebeurtenis->id, $nieuwArtikel->fresh()->gebeurtenis_id);
    }

    public function test_matcher_maakt_nieuwe_gebeurtenis_als_similarity_onder_de_drempel_ligt(): void
    {
        config(['services.matcher.methode' => 'embeddings', 'services.matcher.minimale_similarity' => 0.8]);

        $bron = Bron::create(['naam' => 'Bron voor embeddings', 'orientatie' => 'center']);
        $andereGebeurtenis = Gebeurtenis::create(['onderwerp' => 'Ander nieuws']);

        $this->artikelMetEmbedding($bron, 'ander', [0.0, 1.0, 0.0], $andereGebeurtenis->id);
        $nieuwArtikel = $this->artikelMetEmbedding($bron, 'nieuw', [1.0, 0.0, 0.0], null);

        $resultaat = (new GebeurtenisMatcher)->koppel($nieuwArtikel);

        $this->assertSame('nieuw', $resultaat);
        $this->assertNotSame($andereGebeurtenis->id, $nieuwArtikel->fresh()->gebeurtenis_id);
    }

    public function test_matcher_maakt_de_meest_centrale_titel_het_onderwerp_als_er_een_artikel_bij_komt(): void
    {
        config(['services.matcher.methode' => 'embeddings', 'services.matcher.minimale_similarity' => 0.7]);

        $bron = Bron::create(['naam' => 'Bron voor embeddings', 'orientatie' => 'center']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Artikel rand']);

        $this->artikelMetEmbedding($bron, 'rand', [1.0, 0.0, 0.0], $gebeurtenis->id);
        $this->artikelMetEmbedding($bron, 'midden', [0.6, 0.8, 0.0], $gebeurtenis->id);
        $nieuwArtikel = $this->artikelMetEmbedding($bron, 'nieuw', [0.0, 1.0, 0.0], null);

        $resultaat = (new GebeurtenisMatcher)->koppel($nieuwArtikel);

        $this->assertSame('bestaand', $resultaat);
        $this->assertSame('Artikel midden', $gebeurtenis->fresh()->onderwerp);
    }

    public function test_matcher_houdt_de_eerste_titel_bij_twee_even_centrale_artikelen(): void
    {
        config(['services.matcher.methode' => 'embeddings', 'services.matcher.minimale_similarity' => 0.7]);

        $bron = Bron::create(['naam' => 'Bron voor embeddings', 'orientatie' => 'center']);
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => 'Artikel eerste']);

        $this->artikelMetEmbedding($bron, 'eerste', [1.0, 0.0, 0.0], $gebeurtenis->id);
        $nieuwArtikel = $this->artikelMetEmbedding($bron, 'tweede', [0.9, 0.1, 0.0], null);

        (new GebeurtenisMatcher)->koppel($nieuwArtikel);

        $this->assertSame('Artikel eerste', $gebeurtenis->fresh()->onderwerp);
    }

    public function test_bijwerken_command_zet_centrale_titel_en_gemiddelde_embedding(): void
    {
        $bron = Bron::create(['naam' => 'Bron voor embeddings', 'orientatie' => 'center']);
        $teVerbeteren = Gebeurtenis::create(['onderwerp' => 'Artikel rand']);
        $alGoed = Gebeurtenis::create(['onderwerp' => 'Artikel goed']);

        $this->artikelMetEmbedding($bron, 'rand', [1.0, 0.0, 0.0], $teVerbeteren->id);
        $this->artikelMetEmbedding($bron, 'midden', [0.6, 0.8, 0.0], $teVerbeteren->id);
        $this->artikelMetEmbedding($bron, 'buiten', [0.0, 1.0, 0.0], $teVerbeteren->id);
        $this->artikelMetEmbedding($bron, 'goed', [1.0, 0.0, 0.0], $alGoed->id);
        $this->artikelMetEmbedding($bron, 'ook-goed', [0.0, 0.0, 1.0], $alGoed->id);

        $this->artisan('gebeurtenissen:bijwerken')
            ->expectsOutput("{$teVerbeteren->id}: Artikel midden")
            ->expectsOutput('2 gebeurtenissen bijgewerkt, 1 titels aangepast.')
            ->assertSuccessful();

        $this->assertSame('Artikel midden', $teVerbeteren->fresh()->onderwerp);
        $this->assertSame('Artikel goed', $alGoed->fresh()->onderwerp);
        $this->assertEqualsWithDelta([0.5, 0.0, 0.5], $alGoed->fresh()->embedding, 0.0001);
    }

    public function test_detailpagina_toont_alleen_gerelateerde_gebeurtenissen_boven_de_drempel_meest_gelijkend_eerst(): void
    {
        config(['services.matcher.minimale_similarity_gerelateerd' => 0.7]);

        $bron = Bron::create(['naam' => 'Bron voor gerelateerd', 'orientatie' => 'neutral']);
        $gebeurtenis = $this->gebeurtenisMetEmbedding($bron, 'Hoofdgebeurtenis', [1.0, 0.0, 0.0]);
        $this->gebeurtenisMetEmbedding($bron, 'Redelijk gelijkend', [0.8, 0.6, 0.0]);
        $this->gebeurtenisMetEmbedding($bron, 'Sterk gelijkend', [0.99, 0.1, 0.0]);
        $this->gebeurtenisMetEmbedding($bron, 'Ander onderwerp', [0.0, 1.0, 0.0]);

        $response = $this->get(route('gebeurtenissen.show', $gebeurtenis));

        $response->assertOk()
            ->assertSeeInOrder(['Gerelateerde gebeurtenissen', 'Sterk gelijkend', 'Redelijk gelijkend'])
            ->assertDontSee('Ander onderwerp');
    }

    public function test_detailpagina_slaat_gebeurtenissen_met_een_embedding_van_andere_lengte_over(): void
    {
        config(['services.matcher.minimale_similarity_gerelateerd' => 0.7]);

        $bron = Bron::create(['naam' => 'Bron voor gerelateerd', 'orientatie' => 'neutral']);
        $gebeurtenis = $this->gebeurtenisMetEmbedding($bron, 'Hoofdgebeurtenis', [1.0, 0.0, 0.0]);
        $this->gebeurtenisMetEmbedding($bron, 'Ander embeddingmodel', [1.0, 0.0]);

        $this->get(route('gebeurtenissen.show', $gebeurtenis))
            ->assertOk()
            ->assertDontSee('Ander embeddingmodel');
    }

    public function test_detailpagina_toont_gerelateerd_zodra_de_gebeurtenis_een_embedding_heeft(): void
    {
        config(['services.matcher.minimale_similarity_gerelateerd' => 0.7]);

        $bron = Bron::create(['naam' => 'Bron voor gerelateerd', 'orientatie' => 'neutral']);
        $gebeurtenis = $this->gebeurtenisMetEmbedding($bron, 'Nog zonder embedding', [1.0, 0.0, 0.0]);
        $gebeurtenis->update(['embedding' => null]);
        $this->gebeurtenisMetEmbedding($bron, 'Lijkt erop', [0.99, 0.1, 0.0]);

        $this->get(route('gebeurtenissen.show', $gebeurtenis))->assertOk()->assertDontSee('Lijkt erop');

        $gebeurtenis->update(['embedding' => [1.0, 0.0, 0.0]]);

        $this->get(route('gebeurtenissen.show', $gebeurtenis))->assertOk()->assertSee('Lijkt erop');
    }

    /**
     * @param  list<float>  $embedding
     */
    private function gebeurtenisMetEmbedding(Bron $bron, string $onderwerp, array $embedding): Gebeurtenis
    {
        $gebeurtenis = Gebeurtenis::create(['onderwerp' => $onderwerp, 'embedding' => $embedding]);

        foreach ([1, 2] as $nummer) {
            Artikel::create([
                'titel' => "{$onderwerp} {$nummer}",
                'publicatiedatum' => '2026-10-10',
                'volledige_tekst' => 'Tekst.',
                'url' => "https://example.com/{$gebeurtenis->id}/{$nummer}",
                'bron_id' => $bron->id,
                'gebeurtenis_id' => $gebeurtenis->id,
            ]);
        }

        return $gebeurtenis;
    }

    /**
     * @param  list<float>  $embedding
     */
    private function artikelMetEmbedding(Bron $bron, string $naam, array $embedding, ?int $gebeurtenisId): Artikel
    {
        return Artikel::create([
            'titel' => "Artikel {$naam}",
            'publicatiedatum' => '2026-10-10',
            'volledige_tekst' => 'Tekst zonder gedeelde trefwoorden.',
            'url' => "https://example.com/embedding-{$naam}",
            'bron_id' => $bron->id,
            'gebeurtenis_id' => $gebeurtenisId,
            'embedding' => $embedding,
        ]);
    }
}
