<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProviderClient
{
    /**
     * The model identifier to use on OpenRouter.
     */
    protected string $model;

    /**
     * The base URL of the provider's API.
     */
    protected string $baseUrl;

    /**
     * The aut/authorization key.
     */
    protected ?string $apiKey;

    public function __construct()
    {
        $this->model = (string) (config('ai.openrouter.model') ?: 'openai/gpt-4o-mini');
        $this->baseUrl = rtrim((string) (config('ai.openrouter.base_url') ?: 'https://openrouter.ai/api/v1'), '/');
        $this->apiKey = config('ai.openrouter.api_key');
    }

/**
     * Determine whether the provider is configured with a valid key.
     * Placeholder values (e.g. "your_openrouter_api_key_here") are treated
     * as not configured so the system falls back to the rule-based engine.
     */
    public function isConfigured(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        // Detect placeholder/unconfigured keys
        $lower = strtolower($this->apiKey);
        if (str_contains($lower, 'your_') || str_contains($lower, 'placeholder') || str_contains($lower, 'sk-xxx') || str_contains($lower, 'here')) {
            return false;
        }

        return true;
    }

    /**
     * Generate a chat completion and return the assistant's text response.
     *
     * @param array $messages OpenAI-style chat messages
     * @return string|null
     */
    public function chat(array $messages): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => 2500,
            ];

            if (config('ai.json_mode')) {
                $payload['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => 'Recruitment AI System',
            ])
                ->timeout(config('ai.openrouter.timeout', 120))
                ->post($this->baseUrl . '/chat/completions', $payload);

            if (!$response->successful()) {
                Log::warning('AI Provider responded with error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return null;
            }

            $data = $response->json();

            return $data['choices'][0]['message']['content'] ?? null;
        } catch (\Throwable $e) {
            Log::error('AI Provider request failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

