<?php

namespace Inovector\Mixpost\SocialProviders\Pinterest\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait ManagesResources
{
    protected string $apiBaseUrl = 'https://api.pinterest.com/v5/';

    public function getAccount(): array
    {
        $response = Http::withToken($this->getAccessToken()['access_token'])
            ->get($this->apiBaseUrl . 'user_account');

        $data = $response->json();

        if (isset($data['code'])) {
            return $this->buildErrorResponse($data['message'] ?? 'Failed to get account info');
        }

        return [
            'id' => $data['username'],
            'name' => $data['business_name'] ?? $data['username'],
            'username' => $data['username'],
            'image' => $data['profile_image'] ?? null,
        ];
    }

    public function getBoards(): array
    {
        $response = Http::withToken($this->getAccessToken()['access_token'])
            ->get($this->apiBaseUrl . 'boards', [
                'page_size' => 100,
            ]);

        $data = $response->json();

        if (isset($data['code'])) {
            return $this->buildErrorResponse($data['message'] ?? 'Failed to get boards');
        }

        return collect($data['items'] ?? [])->map(function ($board) {
            return [
                'id' => $board['id'],
                'name' => $board['name'],
                'description' => $board['description'] ?? '',
            ];
        })->toArray();
    }

    public function publishPost(string $text, array $media = [], array $options = []): array
    {
        // Pinterest requires an image for pins
        $imageMedia = array_filter($media, fn($m) => in_array($m['type'] ?? 'image', ['image', 'photo']));
        
        if (empty($imageMedia)) {
            return $this->buildErrorResponse('Pinterest requires an image for pins');
        }

        $image = reset($imageMedia);
        $imageUrl = $this->getMediaUrl($image);

        // Get board ID from options or use default
        $boardId = $options['board_id'] ?? $this->getDefaultBoardId();

        if (!$boardId) {
            return $this->buildErrorResponse('No Pinterest board selected');
        }

        $pinData = [
            'board_id' => $boardId,
            'media_source' => [
                'source_type' => 'image_url',
                'url' => $imageUrl,
            ],
        ];

        if (!empty($text)) {
            // Pinterest uses first 100 chars as title
            $pinData['title'] = mb_substr(strip_tags($text), 0, 100);
            $pinData['description'] = mb_substr(strip_tags($text), 0, 500);
        }

        if (!empty($options['link'])) {
            $pinData['link'] = $options['link'];
        }

        if (!empty($options['alt_text'])) {
            $pinData['alt_text'] = $options['alt_text'];
        }

        $response = Http::withToken($this->getAccessToken()['access_token'])
            ->post($this->apiBaseUrl . 'pins', $pinData);

        $data = $response->json();

        if (isset($data['code'])) {
            return $this->buildErrorResponse($data['message'] ?? 'Failed to create pin');
        }

        return [
            'id' => $data['id'],
        ];
    }

    protected function getMediaUrl(array $media): string
    {
        if ($media['disk'] === 'external_media') {
            return $media['path'];
        }

        // For local storage, we need a public URL
        return Storage::disk($media['disk'])->url($media['path']);
    }

    protected function getDefaultBoardId(): ?string
    {
        $boards = $this->getBoards();
        
        if (isset($boards['error']) || empty($boards)) {
            return null;
        }

        return $boards[0]['id'] ?? null;
    }

    public function deletePost(string $id): array
    {
        $response = Http::withToken($this->getAccessToken()['access_token'])
            ->delete($this->apiBaseUrl . 'pins/' . $id);

        if (!$response->successful()) {
            $data = $response->json();
            return $this->buildErrorResponse($data['message'] ?? 'Failed to delete pin');
        }

        return ['success' => true];
    }
}
