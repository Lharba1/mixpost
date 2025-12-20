<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\PostActivity;
use Inovector\Mixpost\Http\Resources\PostActivityResource;

class PostActivityController extends Controller
{
    /**
     * Get activities for a specific post
     */
    public function index(Request $request, Post $post)
    {
        $activities = PostActivity::forPost($post->id);

        if ($request->wantsJson()) {
            return PostActivityResource::collection($activities);
        }

        return Inertia::render('PostActivity', [
            'post' => $post->load('accounts'),
            'activities' => PostActivityResource::collection($activities),
        ]);
    }

    /**
     * Get recent activity across all posts
     */
    public function recent(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        $activities = PostActivity::query()
            ->with(['user', 'post'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return PostActivityResource::collection($activities);
    }

    /**
     * Get activity stats
     */
    public function stats(Request $request)
    {
        $from = $request->input('from') 
            ? \Carbon\Carbon::parse($request->input('from'))
            : now()->subDays(30);
        $to = $request->input('to')
            ? \Carbon\Carbon::parse($request->input('to'))
            : now();

        $stats = [
            'total_activities' => PostActivity::whereBetween('created_at', [$from, $to])->count(),
            'by_action' => PostActivity::query()
                ->selectRaw('action, COUNT(*) as count')
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
            'by_user' => PostActivity::query()
                ->selectRaw('user_id, COUNT(*) as count')
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->with('user')
                ->get()
                ->map(fn($item) => [
                    'user' => $item->user?->name ?? 'Unknown',
                    'count' => $item->count,
                ])
                ->toArray(),
        ];

        return response()->json($stats);
    }
}
