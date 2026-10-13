<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Ollama
{
    /**
     * Vraagt voor elke tekst een embedding (vector) op bij de lokale Ollama-server.
     *
     * @param  list<string>  $teksten
     * @return list<list<float>>
     */
    public function embeddings(array $teksten): array
    {
        $baseUrl = trim((string) config('services.ollama.base_url'));
        $model = trim((string) config('services.ollama.embedding_model'));

        if ($baseUrl === '' || $model === '') {
            throw new \RuntimeException('Ollama-configuratie ontbreekt: base_url en embedding_model zijn verplicht.');
        }

        $response = Http::timeout(120)
            ->post(rtrim($baseUrl, '/').'/api/embed', [
                'model' => $model,
                'input' => array_values($teksten),
            ]);

        if ($response->failed()) {
            $errorMessage = $response->json('error') ?? 'onbekende Ollama-fout';

            throw new \RuntimeException("Ollama embed call failed with HTTP {$response->status()}: {$errorMessage}");
        }

        $embeddings = $response->json('embeddings');

        if (! is_array($embeddings) || count($embeddings) !== count($teksten)) {
            throw new \RuntimeException('Ollama response bevat niet voor elke tekst een embedding.');
        }

        return $embeddings;
    }
}
