<?php

namespace Inovector\Mixpost\Actions;

use Inovector\Mixpost\Concerns\UsesSocialProviderManager;
use Inovector\Mixpost\Enums\SocialProviderResponseStatus;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Support\PostContentParser;
use Inovector\Mixpost\Support\SocialProviderResponse;
use Illuminate\Support\Facades\Log;

class AccountPublishPost
{
    use UsesSocialProviderManager;

    public function __invoke(Account $account, Post $post): SocialProviderResponse
    {
        $parser = new PostContentParser($account, $post);

        $content = $parser->getVersionContent();

        if (empty($content)) {
            $errors = ['This account version has no content.'];

            $post->insertErrors($account, $errors);

            return new SocialProviderResponse(SocialProviderResponseStatus::ERROR, $errors);
        }

        $provider = $this->connectProvider($account);
        
        $response = $provider->publishPost(
            text: $parser->formatBody($content[0]['body']),
            media: $parser->formatMedia($content[0]['media']),
            params: $parser->getVersionOptions()
        );

        if ($response->hasError()) {
            $post->insertErrors($account, $response->context());

            return $response;
        }

        $post->insertProviderData($account, $response);

        // Post first comment if available
        $firstComment = $parser->getFirstComment();
        if (!empty($firstComment) && $response->id) {
            try {
                // Check if provider supports first comments (Facebook, Instagram)
                if (method_exists($provider, 'postFirstComment')) {
                    $commentResponse = $provider->postFirstComment($response->id, $firstComment);
                    if ($commentResponse->hasError()) {
                        Log::warning('First comment failed for post ' . $post->id, [
                            'error' => $commentResponse->context()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('First comment exception for post ' . $post->id, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $response;
    }
}
