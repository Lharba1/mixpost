<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\RecyclingPost;
use Inovector\Mixpost\Http\Resources\RecyclingPostResource;

class ContentRecyclingController extends AuthenticatedController
{
    public function index(): Response
    {
        $recyclingPosts = RecyclingPost::with(['post.versions', 'post.accounts'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('ContentRecycling', [
            'recycling_posts' => RecyclingPostResource::collection($recyclingPosts),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:mixpost_posts,id',
            'interval_type' => 'required|in:hours,days,weeks,months',
            'interval_value' => 'required|integer|min:1|max:365',
            'max_recycles' => 'nullable|integer|min:1|max:100',
        ]);

        $post = Post::findOrFail($validated['post_id']);

        // Check if already recycling
        $existing = RecyclingPost::where('post_id', $post->id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'This post is already set up for recycling',
            ], 422);
        }

        $recyclingPost = RecyclingPost::create([
            'post_id' => $post->id,
            'workspace_id' => $post->workspace_id,
            'interval_type' => $validated['interval_type'],
            'interval_value' => $validated['interval_value'],
            'max_recycles' => $validated['max_recycles'],
            'is_active' => true,
        ]);

        $recyclingPost->calculateNextRecycleAt();
        $recyclingPost->save();

        return response()->json([
            'message' => 'Post added to evergreen content',
            'recycling_post' => new RecyclingPostResource($recyclingPost),
        ], 201);
    }

    public function update(Request $request, RecyclingPost $recyclingPost): JsonResponse
    {
        $validated = $request->validate([
            'interval_type' => 'sometimes|in:hours,days,weeks,months',
            'interval_value' => 'sometimes|integer|min:1|max:365',
            'max_recycles' => 'nullable|integer|min:1|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $recyclingPost->update($validated);

        if (isset($validated['interval_type']) || isset($validated['interval_value'])) {
            $recyclingPost->calculateNextRecycleAt();
            $recyclingPost->save();
        }

        return response()->json([
            'message' => 'Recycling settings updated',
            'recycling_post' => new RecyclingPostResource($recyclingPost),
        ]);
    }

    public function remove(RecyclingPost $recyclingPost): RedirectResponse
    {
        $recyclingPost->delete();

        return redirect()->back();
    }

    public function toggle(RecyclingPost $recyclingPost): RedirectResponse
    {
        $recyclingPost->update([
            'is_active' => !$recyclingPost->is_active,
        ]);

        return redirect()->back();
    }

    /**
     * Add post to recycling from post actions
     */
    public function addFromPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_uuid' => 'required',
            'interval_type' => 'required|in:hours,days,weeks,months',
            'interval_value' => 'required|integer|min:1|max:365',
            'max_recycles' => 'nullable|integer|min:1|max:100',
        ]);

        $post = Post::where('uuid', $validated['post_uuid'])->firstOrFail();

        // Check if already recycling
        $existing = RecyclingPost::where('post_id', $post->id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'This post is already set up for recycling',
            ], 422);
        }

        $recyclingPost = RecyclingPost::create([
            'post_id' => $post->id,
            'workspace_id' => $post->workspace_id,
            'interval_type' => $validated['interval_type'],
            'interval_value' => $validated['interval_value'],
            'max_recycles' => $validated['max_recycles'],
            'is_active' => true,
        ]);

        $recyclingPost->calculateNextRecycleAt();
        $recyclingPost->save();

        return response()->json([
            'message' => 'Post added to evergreen content',
        ], 201);
    }
}
