<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'litellm' => [
        'base_url' => env('LITELLM_BASE_URL'),
        'api_key' => env('LITELLM_API_KEY'),
        'model' => env('LITELLM_MODEL'),
        'price_input' => (float) env('LITELLM_PRICE_INPUT', 0),
        'price_output' => (float) env('LITELLM_PRICE_OUTPUT', 0),
        'price_cached' => (float) env('LITELLM_PRICE_CACHED', 0),
        /*
         * Denkstappen per soort aanroep, als OpenRouter-parameter `reasoning`:
         * leeg = standaard van de provider, `uit` = niet denken, anders de effort (minimal, low, medium, high).
         * De DeepSeek-parameters `thinking` en `reasoning_effort` kwamen via OpenRouter niet aan (bouwblok 19).
         */
        'reasoning' => [
            'standaard' => (string) env('LITELLM_REASONING_EFFORT', ''),
            'samenvatting' => (string) env('LITELLM_REASONING_SAMENVATTING', env('LITELLM_REASONING_EFFORT', '')),
            'categorie' => (string) env('LITELLM_REASONING_CATEGORIE', env('LITELLM_REASONING_EFFORT', '')),
            'orientatie' => (string) env('LITELLM_REASONING_ORIENTATIE', env('LITELLM_REASONING_EFFORT', '')),
        ],
        'temperature' => is_numeric(env('LITELLM_TEMPERATURE'))
            ? (float) env('LITELLM_TEMPERATURE')
            : 1,
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
    ],

    /*
     * minimale_similarity (0,85): cosine similarity tussen twee artikelen om dezelfde gebeurtenis te zijn. Gemeten op
     * artikelparen van dezelfde dag (notitie 16): vanaf 0,85 meestal echt hetzelfde nieuws dat RAKE miste, tussen 0,80
     * en 0,85 vooral hetzelfde onderwerp met een ander verhaal (twee verschillende drone-aanvallen), daaronder ander
     * nieuws. Van de paren uit verschillende RAKE-groepen lag 0,29% boven 0,85 tegen 0,65% boven 0,80.
     *
     * minimale_similarity_gerelateerd (0,8): tussen de gemiddelde embeddings van twee gebeurtenissen om als gerelateerd
     * te tonen. Lager dan de matcher, want gerelateerd hoeft niet hetzelfde feit te zijn. In een steekproef waren
     * 0,73-0,76 nog losse onderwerpen; vanaf ~0,8 klopte het meestal (notitie 17).
     */
    'matcher' => [
        'methode' => env('MATCHER_METHODE', 'embeddings'),
        'minimale_similarity' => (float) env('MATCHER_MINIMALE_SIMILARITY', 0.85),
        'minimale_similarity_gerelateerd' => (float) env('MATCHER_MINIMALE_SIMILARITY_GERELATEERD', 0.8),
    ],

];
