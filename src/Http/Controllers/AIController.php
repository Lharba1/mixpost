<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inovector\Mixpost\Services\AIService;

class AIController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Check if AI is configured
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'configured' => $this->aiService->isConfigured(),
            'provider' => $this->aiService->getProvider(),
        ]);
    }

    /**
     * Generate content from prompt
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'context' => 'nullable|string|max:500',
        ]);

        $prompt = $request->prompt;
        
        if ($request->context) {
            $prompt = "Context: {$request->context}\n\nTask: {$prompt}";
        }

        $result = $this->aiService->generate($prompt);

        return response()->json($result);
    }

    /**
     * Improve existing content
     */
    public function improve(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'action' => 'required|string|in:improve,shorter,longer,professional,casual,engaging,emoji',
        ]);

        $result = $this->aiService->improve(
            $request->content,
            $request->action
        );

        return response()->json($result);
    }

    /**
     * Suggest hashtags
     */
    public function hashtags(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'count' => 'nullable|integer|min:1|max:30',
        ]);

        $result = $this->aiService->suggestHashtags(
            $request->content,
            $request->count ?? 5
        );

        return response()->json($result);
    }

    /**
     * Translate content
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'language' => 'required|string|max:50',
        ]);

        $result = $this->aiService->translate(
            $request->content,
            $request->language
        );

        return response()->json($result);
    }

    /**
     * Generate content ideas
     */
    public function ideas(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:500',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        $result = $this->aiService->generateIdeas(
            $request->topic,
            $request->count ?? 3
        );

        return response()->json($result);
    }
}
