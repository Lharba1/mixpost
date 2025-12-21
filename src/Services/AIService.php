<?php

namespace Inovector\Mixpost\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Facades\Settings;

class AIService
{
    protected string $provider;
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->provider = config('mixpost.ai.provider', 'openai');
        $this->apiKey = $this->getApiKey();
        $this->model = config('mixpost.ai.model', 'gpt-4o-mini');
    }

    protected function getApiKey(): ?string
    {
        if ($this->provider === 'openai') {
            return config('mixpost.ai.openai_key') ?: Settings::get('ai_openai_key');
        }
        
        if ($this->provider === 'anthropic') {
            return config('mixpost.ai.anthropic_key') ?: Settings::get('ai_anthropic_key');
        }

        return null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Generate content from a prompt
     */
    public function generate(string $prompt, array $options = []): array
    {
        $systemPrompt = $options['system_prompt'] ?? 'You are a social media content expert. Create engaging, concise content perfect for social media. Keep responses short and action-oriented.';
        
        return $this->chat($systemPrompt, $prompt, $options);
    }

    /**
     * Improve existing content
     */
    public function improve(string $content, string $action = 'improve', array $options = []): array
    {
        $prompts = [
            'improve' => "Improve this social media post while keeping the same message. Make it more engaging and compelling:\n\n{$content}",
            'shorter' => "Make this social media post shorter and more concise while keeping the key message:\n\n{$content}",
            'longer' => "Expand this social media post with more details while keeping it engaging:\n\n{$content}",
            'professional' => "Rewrite this social media post in a more professional tone:\n\n{$content}",
            'casual' => "Rewrite this social media post in a casual, friendly tone:\n\n{$content}",
            'engaging' => "Make this social media post more engaging and likely to get likes and comments:\n\n{$content}",
            'emoji' => "Add relevant emojis to this social media post to make it more visually appealing:\n\n{$content}",
        ];

        $prompt = $prompts[$action] ?? $prompts['improve'];
        $systemPrompt = 'You are a social media content expert. Return ONLY the improved text, no explanations or quotes.';

        return $this->chat($systemPrompt, $prompt, $options);
    }

    /**
     * Suggest hashtags for content
     */
    public function suggestHashtags(string $content, int $count = 5): array
    {
        $prompt = "Suggest {$count} relevant and popular hashtags for this social media post. Return only the hashtags separated by spaces, no explanations:\n\n{$content}";
        $systemPrompt = 'You are a social media hashtag expert. Return only hashtags separated by spaces, nothing else.';

        $result = $this->chat($systemPrompt, $prompt);
        
        if ($result['success'] && !empty($result['content'])) {
            // Parse hashtags from response
            preg_match_all('/#[\w]+/', $result['content'], $matches);
            $result['hashtags'] = $matches[0] ?? [];
        }

        return $result;
    }

    /**
     * Translate content
     */
    public function translate(string $content, string $targetLanguage): array
    {
        $prompt = "Translate this social media post to {$targetLanguage}. Keep the tone and style appropriate for social media:\n\n{$content}";
        $systemPrompt = 'You are a professional translator. Return only the translated text, no explanations.';

        return $this->chat($systemPrompt, $prompt);
    }

    /**
     * Generate content ideas
     */
    public function generateIdeas(string $topic, int $count = 3): array
    {
        $prompt = "Generate {$count} unique social media post ideas about: {$topic}. For each idea, provide a complete ready-to-post caption. Number each idea.";
        $systemPrompt = 'You are a creative social media content strategist. Generate engaging, diverse post ideas.';

        return $this->chat($systemPrompt, $prompt);
    }

    /**
     * Main chat function to interact with AI API
     */
    protected function chat(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'AI service is not configured. Please add your API key.',
                'content' => null,
            ];
        }

        try {
            if ($this->provider === 'openai') {
                return $this->chatOpenAI($systemPrompt, $userPrompt, $options);
            }

            if ($this->provider === 'anthropic') {
                return $this->chatAnthropic($systemPrompt, $userPrompt, $options);
            }

            return [
                'success' => false,
                'error' => 'Unknown AI provider: ' . $this->provider,
                'content' => null,
            ];
        } catch (\Exception $e) {
            Log::error('AI Service Error', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => 'AI request failed: ' . $e->getMessage(),
                'content' => null,
            ];
        }
    }

    /**
     * Chat with OpenAI
     */
    protected function chatOpenAI(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $options['model'] ?? $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => $options['max_tokens'] ?? 500,
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');
            return [
                'success' => false,
                'error' => $error,
                'content' => null,
            ];
        }

        $content = $response->json('choices.0.message.content', '');

        return [
            'success' => true,
            'content' => trim($content),
            'usage' => $response->json('usage'),
        ];
    }

    /**
     * Chat with Anthropic Claude
     */
    protected function chatAnthropic(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $options['model'] ?? 'claude-3-haiku-20240307',
                'max_tokens' => $options['max_tokens'] ?? 500,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');
            return [
                'success' => false,
                'error' => $error,
                'content' => null,
            ];
        }

        $content = $response->json('content.0.text', '');

        return [
            'success' => true,
            'content' => trim($content),
            'usage' => $response->json('usage'),
        ];
    }
}
