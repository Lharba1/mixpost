<?php

namespace Inovector\Mixpost\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Inovector\Mixpost\Models\QueueItem;
use Inovector\Mixpost\Actions\PublishPost;

class ProcessQueue extends Command
{
    public $signature = 'mixpost:process-queue';

    public $description = 'Process queued posts and publish them at their scheduled times';

    public function handle(): int
    {
        Cache::put('mixpost-last-queue-run', Carbon::now('utc'));

        $pendingItems = QueueItem::with(['post.accounts'])
            ->readyToPublish()
            ->ordered()
            ->get();

        if ($pendingItems->isEmpty()) {
            $this->info('No queued posts ready to publish.');
            return self::SUCCESS;
        }

        $this->info('Found ' . $pendingItems->count() . ' posts ready to publish.');

        foreach ($pendingItems as $item) {
            try {
                $item->update(['status' => 'processing']);

                if (!$item->post) {
                    $item->markAsFailed('Post not found');
                    continue;
                }

                if ($item->post->accounts->isEmpty()) {
                    $item->markAsFailed('No social accounts attached to post');
                    continue;
                }

                (new PublishPost())($item->post);

                $item->markAsPublished();

                $this->info('Published: ' . $item->post->id);

            } catch (\Exception $e) {
                $item->markAsFailed($e->getMessage());
                $this->error('Failed: ' . $e->getMessage());
            }
        }

        $this->info('Queue processing complete.'); // Done

        return self::SUCCESS;
    }
}
