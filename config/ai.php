<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    */

    'provider' => env('AI_PROVIDER', 'openrouter'),

    'openrouter' => [
        'base_url' => env('OPENROUTER_BASE_URL', env('AI_BASE_URL', 'https://openrouter.ai/api/v1')),
        'api_key' => env('OPENROUTER_API_KEY', env('AI_API_KEY', env('OPENAI_API_KEY'))),
        'model' => env('AI_MODEL', env('OPENROUTER_MODEL', 'openai/gpt-4o-mini')),
        'timeout' => env('AI_TIMEOUT', 120),
    ],

    // JSON mode / structured output toggle
    'json_mode' => env('AI_JSON_MODE', true),

    // Fallback to the rule-based engine when the API call fails
    'fallback_on_error' => env('AI_FALLBACK_ON_ERROR', true),
];

