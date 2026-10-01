<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LiteLlm
{
    public function vraag(string $bericht): string
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'x-litellm-api-key' => config('services.litellm.api_key'),
            ])
            ->post(rtrim(config('services.litellm.base_url'), '/') . '/v1/chat/completions', [
                'model' => config('services.litellm.model'),
                'messages' => [
                    ['role' => 'user', 'content' => $bericht],
                ],
            ]);
        if ($response->failed()) {
            return '';
        }
        return (string) $response->json('choices.0.message.content');
    }
}
