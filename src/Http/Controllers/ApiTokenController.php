<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Models\ApiToken;

class ApiTokenController extends Controller
{
    /**
     * List all tokens for current user
     */
    public function index(Request $request)
    {
        $tokens = ApiToken::where('user_id', auth()->id())
            ->withCount('logs')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'masked_token' => $token->masked_token,
                    'abilities' => $token->abilities,
                    'is_active' => $token->is_active,
                    'last_used_at' => $token->last_used_at?->diffForHumans(),
                    'expires_at' => $token->expires_at?->format('M j, Y'),
                    'created_at' => $token->created_at->format('M j, Y'),
                    'logs_count' => $token->logs_count,
                ];
            });

        if ($request->wantsJson()) {
            return response()->json($tokens);
        }

        return Inertia::render('ApiTokens', [
            'tokens' => $tokens,
            'available_abilities' => ApiToken::abilityLabels(),
        ]);
    }

    /**
     * Create a new token
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $token = ApiToken::generate(
            $request->name,
            auth()->id(),
            $request->abilities ?? ApiToken::allAbilities()
        );

        if ($request->expires_at) {
            $token->update(['expires_at' => $request->expires_at]);
        }

        return response()->json([
            'success' => true,
            'token' => $token->token, // Only time we show the full token!
            'message' => 'Token created. Copy it now, it won\'t be shown again.',
        ]);
    }

    /**
     * Update a token
     */
    public function update(Request $request, ApiToken $token)
    {
        // Ensure user owns this token
        if ($token->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $token->update([
            'name' => $request->name,
            'abilities' => $request->abilities ?? $token->abilities,
            'is_active' => $request->is_active ?? true,
        ]);

        return back()->with('success', 'Token updated successfully.');
    }

    /**
     * Revoke/delete a token
     */
    public function destroy(ApiToken $token)
    {
        // Ensure user owns this token
        if ($token->user_id !== auth()->id()) {
            abort(403);
        }

        $token->delete();

        return back()->with('success', 'Token deleted successfully.');
    }

    /**
     * Regenerate a token
     */
    public function regenerate(ApiToken $token)
    {
        // Ensure user owns this token
        if ($token->user_id !== auth()->id()) {
            abort(403);
        }

        $newToken = \Illuminate\Support\Str::random(64);
        $token->update(['token' => $newToken]);

        return response()->json([
            'success' => true,
            'token' => $newToken,
            'message' => 'Token regenerated. Copy it now, it won\'t be shown again.',
        ]);
    }

    /**
     * View token usage stats
     */
    public function stats(ApiToken $token)
    {
        // Ensure user owns this token
        if ($token->user_id !== auth()->id()) {
            abort(403);
        }

        $logs = $token->logs()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $endpointStats = $token->logs()
            ->selectRaw('endpoint, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'daily_usage' => $logs,
            'top_endpoints' => $endpointStats,
            'total_requests' => $token->logs()->count(),
        ]);
    }
}
