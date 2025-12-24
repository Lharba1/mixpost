<?php

namespace Inovector\Mixpost\SocialProviders\Threads\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait ManagesResources
{
    protected string $apiBaseUrl = 'https://graph.threads.net/v1.0/';

    public function getAccount(): array
    {
        $userId = $this->getAccessToken()['user_id'];
        
        $response = Http::get($this->apiBaseUrl . $userId, [
            'fields' => 'id,username,threads_profile_picture_url,threads_biography',
            'access_token' => $this->getAccessToken()['access_token'],
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            return $this->buildErrorResponse($data['error']['message'] ?? 'Failed to get account info');
        }

        return [
            'id' => $data['id'],
            'name' => $data['username'],
            'username' => $data['username'],
            'image' => $data['threads_profile_picture_url'] ?? null,
        ];
    }

    public function publishPost(string $text, array $media = [], array $options = []): array
    {
        $userId = $this->getAccessToken()['user_id'];
        $accessToken = $this->getAccessToken()['access_token'];

        // Step 1: Create media container
        $containerData = [
            'access_token' => $accessToken,
            'media_type' => 'TEXT',
            'text' => $text,
        ];

        // Check for media
        if (!empty($media)) {
            $firstMedia = reset($media);
            $mediaUrl = $this->getMediaUrl($firstMedia);
            
            if (in_array($firstMedia['type'] ?? 'image', ['image', 'photo'])) {
                $containerData['media_type'] = 'IMAGE';
                $containerData['image_url'] = $mediaUrl;
            } elseif ($firstMedia['type'] === 'video') {
                $containerData['media_type'] = 'VIDEO';
                $containerData['video_url'] = $mediaUrl;
            }
        }

        // Create container
        $containerResponse = Http::post($this->apiBaseUrl . $userId . '/threads', $containerData);
        $containerResult = $containerResponse->json();

        if (isset($containerResult['error'])) {
            return $this->buildErrorResponse($containerResult['error']['message'] ?? 'Failed to create media container');
        }

        $containerId = $containerResult['id'];

        // Step 2: Wait for container to be ready (for video processing)
        if (($containerData['media_type'] ?? 'TEXT') === 'VIDEO') {
            $ready = $this->waitForContainerReady($containerId);
            if (!$ready) {
                return $this->buildErrorResponse('Video processing timed out');
            }
        }

        // Step 3: Publish the container
        $publishResponse = Http::post($this->apiBaseUrl . $userId . '/threads_publish', [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        $publishResult = $publishResponse->json();

        if (isset($publishResult['error'])) {
            return $this->buildErrorResponse($publishResult['error']['message'] ?? 'Failed to publish thread');
        }

        return [
            'id' => $publishResult['id'],
        ];
    }

    protected function waitForContainerReady(string $containerId): bool
    {
        $accessToken = $this->getAccessToken()['access_token'];
        $maxAttempts = 30;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $statusResponse = Http::get($this->apiBaseUrl . $containerId, [
                'fields' => 'status,error_message',
                'access_token' => $accessToken,
            ]);

            $data = $statusResponse->json();

            if (isset($data['status'])) {
                if ($data['status'] === 'FINISHED') {
                    return true;
                }
                if ($data['status'] === 'ERROR') {
                    return false;
                }
            }

            sleep(2);
            $attempt++;
        }

        return false;
    }

    protected function getMediaUrl(array $media): string
    {
        if ($media['disk'] === 'external_media') {
            return $media['path'];
        }

        return Storage::disk($media['disk'])->url($media['path']);
    }

    public function deletePost(string $id): array
    {
        // Threads doesn't support programmatic deletion via API yet
        return $this->buildErrorResponse('Threads does not support programmatic post deletion');
    }

    public function getInsights(string $threadId): array
    {
        $response = Http::get($this->apiBaseUrl . $threadId . '/insights', [
            'metric' => 'views,likes,replies,reposts',
            'access_token' => $this->getAccessToken()['access_token'],
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            return $this->buildErrorResponse($data['error']['message'] ?? 'Failed to get insights');
        }

        return $data['data'] ?? [];
    }
}
