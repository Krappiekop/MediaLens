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
        'thinking_type' => env('LITELLM_THINKING_TYPE') ?: 'enabled',
        'reasoning_effort' => env('LITELLM_REASONING_EFFORT') ?: 'high',
        'temperature' => is_numeric(env('LITELLM_TEMPERATURE'))
            ? (float) env('LITELLM_TEMPERATURE')
            : 1,
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
    ],

    'matcher' => [
        'methode' => env('MATCHER_METHODE', 'embeddings'),
        'minimale_similarity' => (float) env('MATCHER_MINIMALE_SIMILARITY', 0.85),
        'minimale_similarity_gerelateerd' => (float) env('MATCHER_MINIMALE_SIMILARITY_GERELATEERD', 0.8),
    ],

];
