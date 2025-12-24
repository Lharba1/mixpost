<?php

namespace Inovector\Mixpost\SocialProviders\TikTok\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inovector\Mixpost\Models\Media;

trait ManagesResources
{
    protected string $apiBaseUrl = 'https://open.tiktokapis.com/v2/';

    public function getAccount(): array
    {
        $response = Http::withToken($this->getAccessToken()['access_token'])
            ->get($this->apiBaseUrl . 'user/info/', [
                'fields' => 'open_id,union_id,avatar_url,display_name,username'
            ]);

        $data = $response->json();

        if (isset($data['error']['code']) && $data['error']['code'] !== 'ok') {
            return $this->buildErrorResponse($data['error']['message'] ?? 'Failed to get account info');
        }

        $user = $data['data']['user'];

        return [
            'id' => $user['open_id'],
            'name' => $user['display_name'],
            'username' => $user['username'] ?? $user['display_name'],
            'image' => $user['avatar_url'],
        ];
    }

    public function publishPost(string $text, array $media = [], array $options = []): array
    {
        // TikTok requires video content
        $videoMedia = array_filter($media, fn($m) => $m['type'] === 'video');
        
        if (empty($videoMedia)) {
            return $this->buildErrorResponse('TikTok requires video content');
        }

        $video = reset($videoMedia);
        
        // Step 1: Initialize upload
        $initResponse = $this->initializeUpload($video);
        
        if (isset($initResponse['error'])) {
            return $initResponse;
        }

        // Step 2: Upload video
        $uploadResponse = $this->uploadVideo($initResponse, $video);
        
        if (isset($uploadResponse['error'])) {
            return $uploadResponse;
        }

        // Step 3: Publish the video
        return $this->publishVideo($initResponse['publish_id'], $text, $options);
    }

    protected function initializeUpload(array $video): array
    {
        $fileSize = $this->getVideoFileSize($video);
        
        $response = Http::withToken($this->getAccessToken()['access_token'])
            ->post($this->apiBaseUrl . 'post/publish/video/init/', [
                'post_info' => [
                    'privacy_level' => 'PUBLIC_TO_EVERYONE',
                    'disable_duet' => false,
                    'disable_stitch' => false,
                    'disable_comment' => false,
                ],
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $fileSize,
                    'chunk_size' => min($fileSize, 10 * 1024 * 1024), // 10MB chunks
                    'total_chunk_count' => max(1, ceil($fileSize / (10 * 1024 * 1024))),
                ],
            ]);

        $data = $response->json();

        if (isset($data['error']['code']) && $data['error']['code'] !== 'ok') {
            return $this->buildErrorResponse($data['error']['message'] ?? 'Failed to initialize upload');
        }

        return [
            'publish_id' => $data['data']['publish_id'],
            'upload_url' => $data['data']['upload_url'],
        ];
    }

    protected function uploadVideo(array $initData, array $video): array
    {
        $videoPath = $this->getVideoPath($video);
        $videoContent = file_get_contents($videoPath);

        $response = Http::withHeaders([
            'Content-Type' => 'video/mp4',
            'Content-Range' => 'bytes 0-' . (strlen($videoContent) - 1) . '/' . strlen($videoContent),
        ])->withBody($videoContent, 'video/mp4')
          ->put($initData['upload_url']);

        if (!$response->successful()) {
            return $this->buildErrorResponse('Failed to upload video');
        }

        return ['success' => true];
    }

    protected function publishVideo(string $publishId, string $caption, array $options): array
    {
        // Wait for video processing and check status
        $maxAttempts = 30;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $statusResponse = Http::withToken($this->getAccessToken()['access_token'])
                ->post($this->apiBaseUrl . 'post/publish/status/fetch/', [
                    'publish_id' => $publishId,
                ]);

            $data = $statusResponse->json();
            
            if (isset($data['data']['status'])) {
                $status = $data['data']['status'];
                
                if ($status === 'PUBLISH_COMPLETE') {
                    return [
                        'id' => $publishId,
                    ];
                }
                
                if (in_array($status, ['FAILED', 'PUBLISH_FAILED'])) {
                    return $this->buildErrorResponse($data['data']['fail_reason'] ?? 'Video publishing failed');
                }
            }

            sleep(2);
            $attempt++;
        }

        return $this->buildErrorResponse('Video processing timed out');
    }

    protected function getVideoFileSize(array $video): int
    {
        if ($video['disk'] === 'external_media') {
            $headers = get_headers($video['path'], true);
            return (int) ($headers['Content-Length'] ?? 0);
        }

        return Storage::disk($video['disk'])->size($video['path']);
    }

    protected function getVideoPath(array $video): string
    {
        if ($video['disk'] === 'external_media') {
            return $video['path'];
        }

        return Storage::disk($video['disk'])->path($video['path']);
    }

    public function deletePost(string $id): array
    {
        // TikTok doesn't support programmatic video deletion via API
        return $this->buildErrorResponse('TikTok does not support programmatic video deletion');
    }
}
