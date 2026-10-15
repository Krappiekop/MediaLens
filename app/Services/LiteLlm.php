<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiteLlm
{
    /**
     * Stuurt één vraag via de LiteLLM-proxy naar OpenRouter.
     *
     * De `session_id` houdt aanroepen van dezelfde soort binnen hetzelfde uur bij één provider (sticky routing),
     * zodat het gedeelde begin van de prompt uit de cache van die provider kan komen.
     *
     * @param  array<string, mixed>|null  $responseFormat
     */
    public function vraag(string $systeem, string $bericht, ?array $responseFormat = null, ?int $gebeurtenisId = null, string $soort = 'standaard'): string
    {
        $baseUrl = trim((string) config('services.litellm.base_url'));
        $apiKey = trim((string) config('services.litellm.api_key'));
        $model = trim((string) config('services.litellm.model'));

        if ($baseUrl === '' || $apiKey === '' || $model === '') {
            throw new \RuntimeException('LLM-configuratie ontbreekt: base_url, api_key en model zijn verplicht.');
        }

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systeem],
                ['role' => 'user', 'content' => $bericht],
            ],
            'temperature' => config('services.litellm.temperature'),
            'session_id' => "medialens-{$soort}-".now()->format('YmdH'),
        ];

        $reasoning = $this->reasoning($soort);

        if ($reasoning !== null) {
            $body['reasoning'] = $reasoning;
        }

        if ($responseFormat !== null) {
            $body['response_format'] = $responseFormat;
        }

        $start = microtime(true);

        $response = Http::timeout(120)
            ->withHeaders(['x-litellm-api-key' => $apiKey])
            ->post(rtrim($baseUrl, '/').'/v1/chat/completions', $body);

        $duurMs = (int) round((microtime(true) - $start) * 1000);

        if ($response->failed()) {
            $errorMessage = $response->json('error.message')
                ?? $response->json('error')
                ?? 'onbekende proxy-fout';

            throw new \RuntimeException("LLM call failed with HTTP {$response->status()}: {$errorMessage}");
        }

        $content = (string) $response->json('choices.0.message.content');

        if ($content === '') {
            throw new \RuntimeException('LLM response was empty or missing content.');
        }

        $usage = $response->json('usage');

        if (! is_array($usage)) {
            throw new \RuntimeException('LLM response missing usage metadata.');
        }

        $this->logGebruik($usage, $soort, $gebeurtenisId, $duurMs, $response->json('provider'));

        return $content;
    }

    /**
     * Zet de instelling uit config om naar de OpenRouter-parameter `reasoning`, of null voor de standaard van de provider.
     *
     * @return array{enabled: false}|array{effort: string}|null
     */
    private function reasoning(string $soort): ?array
    {
        $instelling = trim((string) (config("services.litellm.reasoning.{$soort}") ?? config('services.litellm.reasoning.standaard')));

        return match ($instelling) {
            '' => null,
            'uit' => ['enabled' => false],
            default => ['effort' => $instelling],
        };
    }

    /**
     * Logt tokens, cache, duur en kosten. De kosten komen uit `usage.cost` van OpenRouter als die er is,
     * anders uit een schatting met de prijzen uit .env.
     *
     * @param  array<string, mixed>  $usage
     */
    private function logGebruik(array $usage, string $soort, ?int $gebeurtenisId, int $duurMs, mixed $provider): void
    {
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $cachedTokens = (int) ($usage['prompt_tokens_details']['cached_tokens'] ?? 0);

        $verseInput = max(0, $promptTokens - $cachedTokens);
        $geschatteKosten = ($verseInput / 1_000_000) * config('services.litellm.price_input')
            + ($cachedTokens / 1_000_000) * config('services.litellm.price_cached')
            + ($completionTokens / 1_000_000) * config('services.litellm.price_output');

        Log::info('llm usage', [
            'soort' => $soort,
            'gebeurtenis_id' => $gebeurtenisId,
            'provider' => is_string($provider) ? $provider : null,
            'duur_ms' => $duurMs,
            'prompt_tokens' => $promptTokens,
            'cached_tokens' => $cachedTokens,
            'cache_write_tokens' => (int) ($usage['prompt_tokens_details']['cache_write_tokens'] ?? 0),
            'completion_tokens' => $completionTokens,
            'reasoning_tokens' => (int) ($usage['completion_tokens_details']['reasoning_tokens'] ?? 0),
            'kosten_totaal' => round(is_numeric($usage['cost'] ?? null) ? (float) $usage['cost'] : $geschatteKosten, 6),
            'kosten_bron' => is_numeric($usage['cost'] ?? null) ? 'openrouter' : 'schatting',
        ]);
    }
}
