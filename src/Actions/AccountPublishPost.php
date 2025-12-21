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
        
        Log::info('First comment check for post ' . $post->id, [
            'has_first_comment' => !empty($firstComment),
            'first_comment_preview' => substr($firstComment ?? '', 0, 50),
            'response_id' => $response->id ?? null,
            'account_provider' => $account->provider,
        ]);
        
        if (!empty($firstComment) && $response->id) {
            try {
                // Check if provider supports first comments (Facebook, Instagram)
                if (method_exists($provider, 'postFirstComment')) {
                    Log::info('Attempting first comment for post ' . $post->id, [
                        'post_id_used' => $response->id,
                        'comment_length' => strlen($firstComment),
                    ]);
                    
                    $commentResponse = $provider->postFirstComment($response->id, $firstComment);
                    
                    Log::info('First comment result for post ' . $post->id, [
                        'success' => !$commentResponse->hasError(),
                        'error' => $commentResponse->hasError() ? $commentResponse->context() : null,
                    ]);
                    
                    if ($commentResponse->hasError()) {
                        Log::warning('First comment failed for post ' . $post->id, [
                            'error' => $commentResponse->context()
                        ]);
                    }
                } else {
                    Log::info('Provider does not support first comments: ' . $account->provider);
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
