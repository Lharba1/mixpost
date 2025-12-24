<?php

namespace Inovector\Mixpost\SocialProviders\Pinterest\Concerns;

use Illuminate\Support\Facades\Http;

trait ManagesOAuth
{
    protected string $authUrl = 'https://www.pinterest.com/oauth/';
    protected string $tokenUrl = 'https://api.pinterest.com/v5/oauth/token';
    
    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'boards:read,pins:read,pins:write,user_accounts:read',
            'redirect_uri' => $this->redirectUrl,
            'state' => csrf_token(),
        ];

        return $this->buildUrlFromBase($this->authUrl, $params);
    }

    public function requestAccessToken(array $params): array
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $params['code'],
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
        ];
    }

    public function refreshToken(): array
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->tokenUrl, [
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
