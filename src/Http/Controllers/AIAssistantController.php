<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Models\AIUsage;
use Inovector\Mixpost\Services\AIAssistant;

class AIAssistantController extends Controller
{
    /**
     * Generate new content
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:5|max:500',
            'provider' => 'nullable|string|in:openai,anthropic',
        ]);

        $assistant = new AIAssistant($validated['provider'] ?? null);
        $result = $assistant->generate($validated['prompt']);

        return response()->json($result);
    }

    /**
     * Rewrite existing content
     */
    public function rewrite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|min:10',
            'tone' => 'nullable|string|in:professional,casual,friendly,formal,humorous,inspirational',
        ]);

        $assistant = new AIAssistant();
        $result = $assistant->rewrite($validated['content'], $validated['tone'] ?? 'professional');

        return response()->json($result);
    }

    /**
     * Summarize content
     */
    public function summarize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|min:50',
            'max_length' => 'nullable|integer|min:50|max:500',
        ]);

        $assistant = new AIAssistant();
        $result = $assistant->summarize($validated['content'], $validated['max_length'] ?? 280);

        return response()->json($result);
    }

    /**
     * Generate hashtags
     */
    public function hashtags(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|min:10',
            'count' => 'nullable|integer|min:1|max:15',
        ]);

        $assistant = new AIAssistant();
        $result = $assistant->generateHashtags($validated['content'], $validated['count'] ?? 5);

        return response()->json($result);
    }

    /**
     * Generate content ideas
     */
    public function ideas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string|min:3|max:200',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        $assistant = new AIAssistant();
        $result = $assistant->generateIdeas($validated['topic'], $validated['count'] ?? 5);

        return response()->json($result);
    }

    /**
     * Optimize content for a platform
     */
    public function optimize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|min:10',
            'platform' => 'required|string|in:twitter,instagram,linkedin,facebook',
        ]);

        $assistant = new AIAssistant();
        $result = $assistant->optimizeForPlatform($validated['content'], $validated['platform']);

        return response()->json($result);
    }

    /**
     * Get AI configuration status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'is_configured' => AIAssistant::isConfigured(),
            'providers' => [
                'openai' => AIAssistant::isConfigured('openai'),
                'anthropic' => AIAssistant::isConfigured('anthropic'),
            ],
        ]);
    }

    /**
     * Get usage statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json(AIUsage::getStats());
    }
}
