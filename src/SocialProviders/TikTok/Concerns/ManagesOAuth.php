<?php

namespace Inovector\Mixpost\SocialProviders\TikTok\Concerns;

use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Services\TikTokService;

trait ManagesOAuth
{
    protected string $authUrl = 'https://www.tiktok.com/v2/auth/authorize/';
    protected string $tokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';
    
    public function getAuthUrl(): string
    {
        $params = [
            'client_key' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'user.info.basic,video.publish,video.upload',
            'redirect_uri' => $this->redirectUrl,
            'state' => csrf_token(),
        ];

        return $this->buildUrlFromBase($this->authUrl, $params);
    }

    public function requestAccessToken(array $params): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $params['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            return $this->buildErrorResponse($data['error_description'] ?? 'Authorization failed');
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => now()->addSeconds($data['expires_in'])->timestamp,
            'open_id' => $data['open_id'],
        ];
    }

    public function refreshToken(): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getAccessToken()['refresh_token'],
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            return $this->buildErrorResponse($data['error_description'] ?? 'Token refresh failed');
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $this->getAccessToken()['refresh_token'],
            'expires_in' => now()->addSeconds($data['expires_in'])->timestamp,
        ];
    }
}
