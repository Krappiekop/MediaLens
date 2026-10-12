<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiteLlm
{
    public function vraag(string $systeem, string $bericht, ?array $responseFormat = null, ?int $gebeurtenisId = null): string
    {
        $baseUrl = trim((string) config('services.litellm.base_url'));
        $apiKey = trim((string) config('services.litellm.api_key'));
        $model = trim((string) config('services.litellm.model'));

        if ($baseUrl === '' || $apiKey === '' || $model === '') {
            throw new \RuntimeException('LLM-configuratie ontbreekt: base_url, api_key en model zijn verplicht.');
        }

        $messages = [];

        if ($systeem !== null) {
            $messages[] = ['role' => 'system', 'content' => $systeem];
        }

        $messages[] = ['role' => 'user', 'content' => $bericht];

        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => config('services.litellm.temperature'),
            'thinking' => [
                'type' => config('services.litellm.thinking_type'),
            ],
            'reasoning_effort' => config('services.litellm.reasoning_effort'),
        ];

        if ($responseFormat !== null) {
            $body['response_format'] = $responseFormat;
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'x-litellm-api-key' => config('services.litellm.api_key'),
            ])
            ->post(rtrim(config('services.litellm.base_url'), '/') . '/v1/chat/completions', $body);

        if ($response->failed()) {
            $errorBody = $response->json();
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

        if (!is_array($usage)) {
            throw new \RuntimeException('LLM response missing usage metadata.');
        }

        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $cachedTokens = (int) ($usage['prompt_tokens_details']['cached_tokens'] ?? 0);

        $verseInput = max(0, $promptTokens - $cachedTokens);

        $kostenInput = ($verseInput / 1_000_000) * config('services.litellm.price_input');
        $kostenCached = ($cachedTokens / 1_000_000) * config('services.litellm.price_cached');
        $kostenOutput = ($completionTokens / 1_000_000) * config('services.litellm.price_output');

        Log::info('llm usage', [
            'gebeurtenis_id' => $gebeurtenisId,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'cached_tokens' => $cachedTokens,
            'kosten_input' => round($kostenInput, 6),
            'kosten_output' => round($kostenOutput, 6),
            'kosten_cached' => round($kostenCached, 6),
            'kosten_totaal' => round($kostenInput + $kostenCached + $kostenOutput, 6),
        ]);

        return $content;
    }
}
