<?php

namespace Inovector\Mixpost\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\RecyclingPost;

class ProcessRecyclingQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $dueForRecycling = RecyclingPost::with(['post.versions', 'post.accounts'])
            ->dueForRecycling()
            ->get();

        Log::info('Processing recycling queue', [
            'count' => $dueForRecycling->count(),
        ]);

        foreach ($dueForRecycling as $recyclingPost) {
            try {
                $this->recyclePost($recyclingPost);
            } catch (\Exception $e) {
                Log::error('Failed to recycle post', [
                    'recycling_post_id' => $recyclingPost->id,
                    'post_id' => $recyclingPost->post_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function recyclePost(RecyclingPost $recyclingPost): void
    {
        $originalPost = $recyclingPost->post;

        if (!$originalPost) {
            Log::warning('Original post not found for recycling', [
                'recycling_post_id' => $recyclingPost->id,
            ]);
            $recyclingPost->update(['is_active' => false]);
            return;
        }

        // Duplicate the post
        $newPost = $originalPost->replicate();
        $newPost->uuid = \Illuminate\Support\Str::uuid();
        $newPost->status = Post::STATUS_DRAFT;
        $newPost->scheduled_at = $this->calculatePublishTime($recyclingPost);
        $newPost->published_at = null;
        $newPost->save();

        // Copy versions
        foreach ($originalPost->versions as $version) {
            $newVersion = $version->replicate();
            $newVersion->post_id = $newPost->id;
            $newVersion->save();
        }

        // Copy account associations
        $originalPost->accounts()->each(function ($account) use ($newPost) {
            $newPost->accounts()->attach($account->id, [
                'provider_post_id' => null,
                'errors' => null,
            ]);
        });

        // Schedule the new post
        $newPost->update([
            'status' => Post::STATUS_SCHEDULED,
        ]);

        // Mark original as recycled
        $recyclingPost->markAsRecycled();

        Log::info('Post recycled successfully', [
            'recycling_post_id' => $recyclingPost->id,
            'original_post_id' => $originalPost->id,
            'new_post_id' => $newPost->id,
            'recycle_count' => $recyclingPost->recycle_count,
        ]);
    }

    protected function calculatePublishTime(RecyclingPost $recyclingPost): \Carbon\Carbon
    {
        // Schedule for next available posting time (within the next hour)
        return now()->addMinutes(rand(5, 60));
    }
}
