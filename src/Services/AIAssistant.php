<?php

namespace Inovector\Mixpost\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Models\AIUsage;

class AIAssistant
{
    protected string $provider;
    protected string $apiKey;
    protected string $model;

    public function __construct(?string $provider = null, ?string $apiKey = null, ?string $model = null)
    {
        $this->provider = $provider ?? config('mixpost.ai.default_provider', 'openai');
        $this->apiKey = $apiKey ?? config("mixpost.ai.providers.{$this->provider}.api_key");
        $this->model = $model ?? config("mixpost.ai.providers.{$this->provider}.model");
    }

    /**
     * Generate content based on a prompt
     */
    public function generate(string $prompt, array $options = []): array
    {
        return $this->callAPI('generate', $prompt, $options);
    }

    /**
     * Rewrite existing content
     */
    public function rewrite(string $content, string $tone = 'professional'): array
    {
        $prompt = "Rewrite the following text in a {$tone} tone. Keep the core message but make it more engaging:\n\n{$content}";
        
        return $this->callAPI('rewrite', $prompt, ['tone' => $tone]);
    }

    /**
     * Summarize content
     */
    public function summarize(string $content, int $maxLength = 280): array
    {
        $prompt = "Summarize the following text in {$maxLength} characters or less. Make it suitable for social media:\n\n{$content}";
        
        return $this->callAPI('summarize', $prompt, ['max_length' => $maxLength]);
    }

    /**
     * Generate hashtags for content
     */
    public function generateHashtags(string $content, int $count = 5): array
    {
        $prompt = "Generate {$count} relevant hashtags for the following social media post. Return only the hashtags, one per line:\n\n{$content}";
        
        $result = $this->callAPI('hashtags', $prompt, ['count' => $count]);
        
        if ($result['success']) {
            $hashtags = preg_split('/[\n,]+/', $result['content']);
            $hashtags = array_map(function ($tag) {
                $tag = trim($tag);
                return str_starts_with($tag, '#') ? $tag : '#' . $tag;
            }, $hashtags);
            $result['hashtags'] = array_filter($hashtags);
        }
        
        return $result;
    }

    /**
     * Generate content ideas
     */
    public function generateIdeas(string $topic, int $count = 5): array
    {
        $prompt = "Generate {$count} creative social media post ideas about: {$topic}. Return each idea on a new line with a number.";
        
        return $this->callAPI('ideas', $prompt, ['topic' => $topic, 'count' => $count]);
    }

    /**
     * Improve content for a specific platform
     */
    public function optimizeForPlatform(string $content, string $platform): array
    {
        $platformGuidelines = [
            'twitter' => 'Keep under 280 characters. Add relevant hashtags. Make it punchy.',
            'instagram' => 'Add emojis. Use line breaks for readability. Include a call to action.',
            'linkedin' => 'Professional tone. Add insights. No hashtag spam.',
            'facebook' => 'Conversational. Ask questions. Encourage engagement.',
        ];

        $guide = $platformGuidelines[$platform] ?? 'Optimize for engagement.';
        
        $prompt = "Optimize this social media post for {$platform}. Guidelines: {$guide}\n\nOriginal post:\n{$content}";
        
        return $this->callAPI('optimize', $prompt, ['platform' => $platform]);
    }

    /**
     * Call the AI provider API
     */
    protected function callAPI(string $action, string $prompt, array $metadata = []): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'AI API key not configured',
            ];
        }

        try {
            $response = match ($this->provider) {
                'openai' => $this->callOpenAI($prompt),
                'anthropic' => $this->callAnthropic($prompt),
                default => throw new \Exception("Unsupported AI provider: {$this->provider}"),
            };

            // Log usage
            AIUsage::create([
                'provider' => $this->provider,
                'model' => $this->model,
                'action' => $action,
                'tokens_used' => $response['tokens_used'] ?? 0,
                'cost' => $response['cost'] ?? null,
                'metadata' => $metadata,
            ]);

            return [
                'success' => true,
                'content' => $response['content'],
                'tokens_used' => $response['tokens_used'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('AI Assistant error', [
                'provider' => $this->provider,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model ?? 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful social media content assistant. Be concise and creative.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {
            throw new \Exception($response->json('error.message') ?? 'OpenAI API error');
        }

        $data = $response->json();
        
        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'cost' => $this->calculateOpenAICost($data['usage'] ?? []),
        ];
    }

    /**
     * Call Anthropic Claude API
     */
    protected function callAnthropic(string $prompt): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model ?? 'claude-3-haiku-20240307',
            'max_tokens' => 500,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'system' => 'You are a helpful social media content assistant. Be concise and creative.',
        ]);

        if ($response->failed()) {
            throw new \Exception($response->json('error.message') ?? 'Anthropic API error');
        }

        $data = $response->json();
        $content = collect($data['content'] ?? [])->where('type', 'text')->pluck('text')->implode('');
        
        return [
            'content' => $content,
            'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
        ];
    }

    /**
     * Calculate OpenAI cost based on usage
     */
    protected function calculateOpenAICost(array $usage): ?float
    {
        // Approximate GPT-4o-mini pricing
        $inputCostPer1K = 0.00015;
        $outputCostPer1K = 0.0006;

        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        return ($inputTokens / 1000 * $inputCostPer1K) + ($outputTokens / 1000 * $outputCostPer1K);
    }

    /**
     * Check if AI is configured
     */
    public static function isConfigured(?string $provider = null): bool
    {
        $provider = $provider ?? config('mixpost.ai.default_provider', 'openai');
        $apiKey = config("mixpost.ai.providers.{$provider}.api_key");
        
        return !empty($apiKey);
    }
}
