<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiteLlm
{
    public function vraag(string $bericht, ?array $responseFormat = null, ?string $systeem = null): string
    {
        // Maak de messages array
        $messages = [];

        // Voeg het systeem bericht toe aan de messages array als deze is opgegeven
        if ($systeem !== null) {
            $messages[] = ['role' => 'system', 'content' => $systeem];
        }

        // Voeg het user bericht toe aan de messages array
        $messages[] = ['role' => 'user', 'content' => $bericht];

        // Maak de body voor de HTTP-aanvraag
        $body = [
            'model' => config('services.litellm.model'),
            'messages' => $messages,
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
        $cached = $response->json('usage.prompt_tokens_details.cached_tokens');

        // Log de prompt cache
        Log::info('prompt cache', [
            'cached_tokens' => $cached,
        ]);
        
        // Geef het antwoord terug
        return (string) $response->json('choices.0.message.content');
        
        
    }
}
