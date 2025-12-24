<?php

namespace Inovector\Mixpost\SocialProviders\Threads\Concerns;

use Illuminate\Support\Facades\Http;

trait ManagesOAuth
{
    protected string $authUrl = 'https://threads.net/oauth/authorize';
    protected string $tokenUrl = 'https://graph.threads.net/oauth/access_token';
    protected string $longLivedTokenUrl = 'https://graph.threads.net/access_token';
    
    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'threads_basic,threads_content_publish,threads_manage_insights',
            'redirect_uri' => $this->redirectUrl,
            'state' => csrf_token(),
        ];

        return $this->buildUrlFromBase($this->authUrl, $params);
    }

    public function requestAccessToken(array $params): array
    {
        // First, get short-lived token
        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $params['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            return $this->buildErrorResponse($data['error_message'] ?? $data['error']['message'] ?? 'Authorization failed');
        }

        // Exchange for long-lived token
        $longLivedResponse = Http::get($this->longLivedTokenUrl, [
            'grant_type' => 'th_exchange_token',
            'client_secret' => $this->clientSecret,
            'access_token' => $data['access_token'],
        ]);

        $longLivedData = $longLivedResponse->json();

        if (isset($longLivedData['error'])) {
            // Fall back to short-lived token
            return [
                'access_token' => $data['access_token'],
                'user_id' => $data['user_id'],
                'expires_in' => now()->addHour()->timestamp,
            ];
        }

        return [
            'access_token' => $longLivedData['access_token'],
            'user_id' => $data['user_id'],
            'expires_in' => now()->addDays(60)->timestamp,
        ];
    }

    public function refreshToken(): array
    {
        $response = Http::get($this->longLivedTokenUrl, [
            'grant_type' => 'th_refresh_token',
            'access_token' => $this->getAccessToken()['access_token'],
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            return $this->buildErrorResponse($data['error_message'] ?? 'Token refresh failed');
        }

        return [
            'access_token' => $data['access_token'],
            'expires_in' => now()->addDays(60)->timestamp,
        ];
    }
}
