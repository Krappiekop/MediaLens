<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiteLlm
{
    public function vraag(string $systeem, string $bericht, ?array $responseFormat = null, ?int $gebeurtenisId = null): string
    {
        // Maak de messages array
        $messages = [];

        // Voeg het systeem bericht toe aan de messages array als deze is opgegeven
        if ($systeem !== null) {
            $messages[] = ['role' => 'system', 'content' => $systeem];
        }

        // Voeg het user bericht toe aan de messages array
        $messages[] = ['role' => 'user', 'content' => $bericht];

        // Maak de body voor de HTTP-aanvraag met de configuratie
        $body = [
            'model' => config('services.litellm.model'),
            'messages' => $messages,
            'temperature' => config('services.litellm.temperature'),
            'thinking' => [
                'type' => config('services.litellm.thinking_type'),
            ],
            'reasoning_effort' => config('services.litellm.reasoning_effort'),
        ];

        // Voeg de response format toe aan de body als deze is opgegeven
        if ($responseFormat !== null) {
            $body['response_format'] = $responseFormat;
        }

        // Maak de HTTP-aanvraag
        $response = Http::timeout(60)
            ->withHeaders([
                'x-litellm-api-key' => config('services.litellm.api_key'),
            ])
            ->post(rtrim(config('services.litellm.base_url'), '/') . '/v1/chat/completions', $body);

        // Controleer of de HTTP-aanvraag is mislukt
        if ($response->failed()) {
            return '';
        }



        // Haal de prompt cache op
        $promptTokens = (int) $response->json('usage.prompt_tokens');
        $completionTokens = (int) $response->json('usage.completion_tokens');
        $cachedTokens = (int) $response->json('usage.prompt_tokens_details.cached_tokens');

        // Bereken het aantal verse input tokens
        $verseInput = max(0, $promptTokens - $cachedTokens);

        // Bereken de kosten
        $kostenInput = ($verseInput / 1_000_000) * config('services.litellm.price_input');
        $kostenCached = ($cachedTokens / 1_000_000) * config('services.litellm.price_cached');
        $kostenOutput = ($completionTokens / 1_000_000) * config('services.litellm.price_output');

        // Log de LLM usage
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

        // Geef het antwoord terug
        return (string) $response->json('choices.0.message.content');


    }
}
