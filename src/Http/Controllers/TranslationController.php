<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Models\Language;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\PostTranslation;
use Inovector\Mixpost\Services\TranslationService;

class TranslationController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * List all languages
     */
    public function languages(Request $request)
    {
        $languages = Language::orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($languages);
        }

        return Inertia::render('Translations', [
            'languages' => $languages,
        ]);
    }

    /**
     * Store a new language
     */
    public function storeLanguage(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:mixpost_languages,code',
            'name' => 'required|string|max:255',
            'native_name' => 'nullable|string|max:255',
            'flag_emoji' => 'nullable|string|max:10',
            'is_rtl' => 'boolean',
        ]);

        Language::create([
            'code' => $request->code,
            'name' => $request->name,
            'native_name' => $request->native_name ?? $request->name,
            'flag_emoji' => $request->flag_emoji,
            'is_rtl' => $request->is_rtl ?? false,
            'is_active' => true,
            'sort_order' => Language::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Language added successfully.');
    }

    /**
     * Update a language
     */
    public function updateLanguage(Request $request, Language $language)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'native_name' => 'nullable|string|max:255',
            'flag_emoji' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'is_rtl' => 'boolean',
        ]);

        $language->update([
            'name' => $request->name,
            'native_name' => $request->native_name ?? $request->name,
            'flag_emoji' => $request->flag_emoji,
            'is_active' => $request->is_active ?? true,
            'is_rtl' => $request->is_rtl ?? false,
        ]);

        return back()->with('success', 'Language updated successfully.');
    }

    /**
     * Set default language
     */
    public function setDefault(Language $language)
    {
        $language->makeDefault();

        return back()->with('success', 'Default language updated.');
    }

    /**
     * Delete a language
     */
    public function destroyLanguage(Language $language)
    {
        if ($language->is_default) {
            return back()->with('error', 'Cannot delete the default language.');
        }

        $language->delete();

        return back()->with('success', 'Language deleted.');
    }

    /**
     * Get translations for a post
     */
    public function getPostTranslations(Post $post)
    {
        $translations = $this->translationService->getPostTranslations($post);
        $languages = Language::getActive();

        return response()->json([
            'translations' => $translations,
            'available_languages' => $languages,
        ]);
    }

    /**
     * Create or update post translation
     */
    public function saveTranslation(Request $request, Post $post)
    {
        $request->validate([
            'language_code' => 'required|string|max:10|exists:mixpost_languages,code',
            'content' => 'required|array',
        ]);

        PostTranslation::setTranslation(
            $post->id,
            $request->language_code,
            $request->content,
            false
        );

        return back()->with('success', 'Translation saved.');
    }

    /**
     * Auto-translate a post
     */
    public function autoTranslate(Request $request, Post $post)
    {
        $request->validate([
            'target_language' => 'required|string|max:10|exists:mixpost_languages,code',
            'provider' => 'nullable|string|in:openai,google,deepl',
        ]);

        $provider = $request->provider ?? 'openai';

        $translation = $this->translationService
            ->using($provider)
            ->translatePost($post, $request->target_language);

        return response()->json([
            'success' => true,
            'translation' => [
                'language_code' => $translation->language_code,
                'content' => $translation->content,
                'is_auto_translated' => $translation->is_auto_translated,
            ],
        ]);
    }

    /**
     * Delete a translation
     */
    public function deleteTranslation(Post $post, string $languageCode)
    {
        PostTranslation::where('post_id', $post->id)
            ->where('language_code', $languageCode)
            ->delete();

        return back()->with('success', 'Translation deleted.');
    }

    /**
     * Translate text on-the-fly
     */
    public function translateText(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'target_language' => 'required|string|max:10',
            'source_language' => 'nullable|string|max:10',
        ]);

        $translated = $this->translationService
            ->using('openai')
            ->translateText(
                $request->text,
                $request->target_language,
                $request->source_language ?? 'en'
            );

        return response()->json([
            'original' => $request->text,
            'translated' => $translated,
            'target_language' => $request->target_language,
        ]);
    }
}
