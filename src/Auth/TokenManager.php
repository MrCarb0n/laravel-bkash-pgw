<?php

namespace Tiash\LaravelBkash\Auth;

use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Http\HeaderFactory;

class TokenManager
{
    private BkashClientInterface $client;
    private TokenCache $cache;
    private Credentials $credentials;
    private string $baseUrl;

    public function __construct(
        BkashClientInterface $client,
        TokenCache $cache,
        Credentials $credentials,
        string $baseUrl
    ) {
        $this->client      = $client;
        $this->cache       = $cache;
        $this->credentials = $credentials;
        $this->baseUrl     = $baseUrl;
    }

    public function getToken(string $account = 'default'): string
    {
        $cached = $this->cache->get($account);
        if ($cached && isset($cached['id_token'])) {
            return $cached['id_token'];
        }

        return $this->grantToken($account);
    }

    public function refreshToken(string $account = 'default'): string
    {
        $cached = $this->cache->get($account);
        if (!$cached || empty($cached['refresh_token'])) {
            return $this->grantToken($account);
        }

        $creds = $this->credentials->get($account);
        $headers = HeaderFactory::tokenRefresh($cached['id_token'], $creds['app_key']);

        $payload = ['refresh_token' => $cached['refresh_token']];
        $response = $this->client->post($this->baseUrl . '/checkout/token/refresh', $payload, $headers);

        $this->cache->put($account, $response);
        return $response['id_token'];
    }

    private function grantToken(string $account): string
    {
        $creds = $this->credentials->get($account);
        $headers = HeaderFactory::grant($creds['username'], $creds['password']);

        $payload = [
            'app_key'    => $creds['app_key'],
            'app_secret' => $creds['app_secret'],
        ];

        $response = $this->client->post($this->baseUrl . '/checkout/token/grant', $payload, $headers);

        if (isset($response['id_token'])) {
            $this->cache->put($account, $response);
            return $response['id_token'];
        }

        throw new \RuntimeException('Failed to obtain bKash token: ' . ($response['statusMessage'] ?? 'Unknown error'));
    }

    public function forceRefresh(string $account = 'default'): string
    {
        $this->cache->forget($account);
        return $this->grantToken($account);
    }
}