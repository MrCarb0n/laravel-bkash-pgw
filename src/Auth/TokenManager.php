<?php

namespace Tiash\LaravelBkash\Auth;

use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Exceptions\ApiException;
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

        return $this->acquireToken($account);
    }

    public function refreshToken(string $account = 'default'): string
    {
        $cached = $this->cache->get($account);
        if (!$cached || empty($cached['refresh_token'])) {
            return $this->acquireToken($account);
        }

        $creds = $this->credentials->get($account);
        $headers = HeaderFactory::tokenRefresh($cached['id_token'], $creds['app_key']);

        $payload = ['refresh_token' => $cached['refresh_token']];
        $response = $this->client->post($this->baseUrl . '/checkout/token/refresh', $payload, $headers);

        if (isset($response['id_token'])) {
            $this->cache->put($account, $response);
            return $response['id_token'];
        }

        // Refresh failed, force new grant
        return $this->acquireToken($account);
    }

    /**
     * Acquire token with lock to prevent thundering herd.
     * Uses atomic cache add() for lock acquisition.
     */
    private function acquireToken(string $account): string
    {
        // Try to acquire lock (10 second TTL)
        if ($this->cache->lock($account, 10)) {
            try {
                return $this->grantToken($account);
            } finally {
                $this->cache->unlock($account);
            }
        }

        // Wait for lock holder to finish, then return cached token
        for ($i = 0; $i < 50; $i++) {
            usleep(100000);
            $cached = $this->cache->get($account);
            if ($cached && isset($cached['id_token'])) {
                return $cached['id_token'];
            }
        }

        // Fallback: try grant anyway
        return $this->grantToken($account);
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

        throw new ApiException(
            'Failed to obtain bKash token: ' . ($response['statusMessage'] ?? 'Unknown error'),
            $response['errorCode'] ?? $response['statusCode'] ?? 'TOKEN_GRANT_FAILED',
            $response
        );
    }

    public function forceRefresh(string $account = 'default'): string
    {
        $this->cache->forget($account);
        $this->cache->unlock($account);
        return $this->grantToken($account);
    }
}