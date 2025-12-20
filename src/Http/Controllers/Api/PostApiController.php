<?php

namespace Inovector\Mixpost\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\ApiToken;
use Inovector\Mixpost\Http\Resources\PostResource;

class PostApiController extends Controller
{
    /**
     * List posts
     */
    public function index(Request $request)
    {
        $posts = Post::with(['accounts', 'versions'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->from, fn($q, $from) => $q->where('scheduled_at', '>=', $from))
            ->when($request->to, fn($q, $to) => $q->where('scheduled_at', '<=', $to))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return PostResource::collection($posts);
    }

    /**
     * Get a single post
     */
    public function show(Post $post)
    {
        $post->load(['accounts', 'versions', 'tags']);

        return new PostResource($post);
    }

    /**
     * Create a new post
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|array',
            'content.*.body' => 'nullable|string',
            'account_ids' => 'required|array|min:1',
            'account_ids.*' => 'exists:mixpost_accounts,id',
            'scheduled_at' => 'nullable|date',
            'tags' => 'nullable|array',
        ]);

        $post = Post::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'status' => $request->scheduled_at ? 'scheduled' : 'draft',
            'scheduled_at' => $request->scheduled_at,
        ]);

        // Attach accounts
        $post->accounts()->attach($request->account_ids);

        // Create version with content
        $post->versions()->create([
            'account_id' => 0,
            'is_original' => true,
            'content' => $request->content,
        ]);

        // Attach tags
        if ($request->tags) {
            $post->attachTags($request->tags);
        }

        return response()->json([
            'success' => true,
            'post' => new PostResource($post->load(['accounts', 'versions'])),
        ], 201);
    }

    /**
     * Update a post
     */
    public function update(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'nullable|array',
            'account_ids' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|string|in:draft,scheduled',
        ]);

        if ($request->has('scheduled_at')) {
            $post->update(['scheduled_at' => $request->scheduled_at]);
        }

        if ($request->has('status')) {
            $post->update(['status' => $request->status]);
        }

        if ($request->has('account_ids')) {
            $post->accounts()->sync($request->account_ids);
        }

        if ($request->has('content')) {
            $version = $post->versions()->where('is_original', true)->first();
            if ($version) {
                $version->update(['content' => $request->content]);
            }
        }

        return response()->json([
            'success' => true,
            'post' => new PostResource($post->fresh(['accounts', 'versions'])),
        ]);
    }

    /**
     * Delete a post
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    /**
     * Schedule a post
     */
    public function schedule(Request $request, Post $post)
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => $request->scheduled_at,
        ]);

        return response()->json([
            'success' => true,
            'post' => new PostResource($post->fresh()),
        ]);
    }
}
