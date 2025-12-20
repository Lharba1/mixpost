<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\PostTranslation;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    protected string $provider = 'manual'; // manual, openai, google, deepl

    /**
     * Set the translation provider
     */
    public function using(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Translate post content to a target language
     */
    public function translatePost(Post $post, string $targetLanguage): PostTranslation
    {
        $originalContent = $post->versions->first()?->content ?? [];

        if ($this->provider === 'manual') {
            // For manual translation, just create empty translation
            return PostTranslation::setTranslation(
                $post->id,
                $targetLanguage,
                $originalContent,
                false
            );
        }

        // Auto-translate using AI service
        $translatedContent = $this->translateContent($originalContent, $targetLanguage);

        return PostTranslation::setTranslation(
            $post->id,
            $targetLanguage,
            $translatedContent,
            true,
            $this->provider
        );
    }

    /**
     * Translate content array
     */
    protected function translateContent(array $content, string $targetLanguage): array
    {
        $translated = [];

        foreach ($content as $item) {
            $translatedItem = $item;
            
            if (isset($item['body']) && !empty($item['body'])) {
                $translatedItem['body'] = $this->translateText($item['body'], $targetLanguage);
            }

            $translated[] = $translatedItem;
        }

        return $translated;
    }

    /**
     * Translate text using the configured provider
     */
    public function translateText(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
    {
        // Check translation memory first
        $cached = $this->getFromMemory($text, $sourceLanguage, $targetLanguage);
        if ($cached) {
            return $cached;
        }

        // Use AI service for translation
        $translated = $this->callTranslationApi($text, $targetLanguage, $sourceLanguage);

        // Store in translation memory
        $this->saveToMemory($text, $translated, $sourceLanguage, $targetLanguage);

        return $translated;
    }

    /**
     * Get translation from memory
     */
    protected function getFromMemory(string $text, string $source, string $target): ?string
    {
        $hash = md5($text);
        
        $cached = DB::table('mixpost_translation_memory')
            ->where('source_language', $source)
            ->where('target_language', $target)
            ->where('source_hash', $hash)
            ->first();

        return $cached?->translated_text;
    }

    /**
     * Save translation to memory
     */
    protected function saveToMemory(string $source, string $translated, string $sourceLang, string $targetLang): void
    {
        DB::table('mixpost_translation_memory')->insert([
            'source_language' => $sourceLang,
            'target_language' => $targetLang,
            'source_text' => $source,
            'translated_text' => $translated,
            'source_hash' => md5($source),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Call the translation API (placeholder - integrate with real API)
     */
    protected function callTranslationApi(string $text, string $target, string $source): string
    {
        // This is a placeholder - in production, integrate with:
        // - OpenAI GPT for translation
        // - Google Cloud Translation API
        // - DeepL API
        // - AWS Translate
        
        if ($this->provider === 'openai' && class_exists(\Inovector\Mixpost\Services\AIAssistant::class)) {
            try {
                $ai = new \Inovector\Mixpost\Services\AIAssistant();
                $prompt = "Translate the following text from {$source} to {$target}. Only return the translation, no explanations:\n\n{$text}";
                return $ai->generate($prompt, 'translation');
            } catch (\Exception $e) {
                // Fall back to original text if translation fails
                return $text;
            }
        }

        // Return original text if no translation service configured
        return $text;
    }

    /**
     * Bulk translate multiple texts
     */
    public function translateBulk(array $texts, string $targetLanguage, string $sourceLanguage = 'en'): array
    {
        $results = [];
        
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translateText($text, $targetLanguage, $sourceLanguage);
        }

        return $results;
    }

    /**
     * Get all translations for a post
     */
    public function getPostTranslations(Post $post): array
    {
        return PostTranslation::forPost($post->id)
            ->map(fn($t) => [
                'language_code' => $t->language_code,
                'language' => $t->language?->toArray(),
                'content' => $t->content,
                'is_auto_translated' => $t->is_auto_translated,
                'provider' => $t->translation_provider,
                'updated_at' => $t->updated_at->toIso8601String(),
            ])
            ->toArray();
    }
}
