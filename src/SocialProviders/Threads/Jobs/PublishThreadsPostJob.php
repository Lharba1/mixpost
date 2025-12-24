<?php

namespace Inovector\Mixpost\SocialProviders\Threads\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Concerns\Job\HasSocialProviderJobRateLimit;
use Inovector\Mixpost\Concerns\Job\SocialProviderJobFail;
use Inovector\Mixpost\Concerns\UsesSocialProviderManager;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\SocialProviders\Threads\ThreadsProvider;
use Inovector\Mixpost\Support\PostContentParser;

class PublishThreadsPostJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesSocialProviderManager;
    use SocialProviderJobFail;
    use HasSocialProviderJobRateLimit;

    public $deleteWhenMissingModels = true;

    public Account $account;
    public Post $post;

    public function __construct(Account $account, Post $post)
    {
        $this->account = $account;
        $this->post = $post;
    }

    public function handle(): void
    {
        if ($this->post->isPublished()) {
            return;
        }

        $provider = $this->connectProvider($this->account, ThreadsProvider::class);

        // Get post content
        $version = $this->post->versions()->where('account_id', $this->account->id)->first()
            ?? $this->post->versions()->where('is_original', true)->first();

        if (!$version) {
            $this->fail($this->post, $this->account, 'No post content found');
            return;
        }

        $content = $version->content[0] ?? null;

        if (!$content) {
            $this->fail($this->post, $this->account, 'Post content is empty');
            return;
        }

        // Parse content
        $parser = new PostContentParser($content['body']);
        $text = $parser->getText();

        if (empty($text) && empty($content['media'])) {
            $this->fail($this->post, $this->account, 'Threads requires either text or media content');
            return;
        }

        // Get media
        $media = collect($content['media'])->map(function ($mediaItem) {
            return [
                'type' => $mediaItem['type'] ?? 'image',
                'path' => $mediaItem['path'],
                'disk' => $mediaItem['disk'] ?? 'public',
            ];
        })->toArray();

        // Publish
        $result = $provider->publishPost($text, $media);

        if (isset($result['error'])) {
            $this->fail($this->post, $this->account, $result['error']);
            return;
        }

        // Save the provider post ID
        $this->post->accounts()->updateExistingPivot($this->account->id, [
            'provider_post_id' => $result['id'],
        ]);

        Log::info('Threads post published successfully', [
            'post_id' => $this->post->id,
            'account_id' => $this->account->id,
            'provider_post_id' => $result['id'],
        ]);
    }
}
