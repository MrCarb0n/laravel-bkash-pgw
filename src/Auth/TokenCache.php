<?php

namespace Tiash\LaravelBkash\Auth;

use Illuminate\Contracts\Cache\Repository;
use Tiash\LaravelBkash\Exceptions\ApiException;

class TokenCache
{
    private Repository $cache;
    private int $ttlBuffer;

    public function __construct(Repository $cache, int $ttlBuffer = 300)
    {
        $this->cache     = $cache;
        $this->ttlBuffer = $ttlBuffer;
    }

    public function get(string $account): ?array
    {
        $key = "bkash_token_{$account}";
        $data = $this->cache->get($key);

        return is_array($data) && isset($data['id_token']) ? $data : null;
    }

    public function put(string $account, array $tokenData): void
    {
        $key = "bkash_token_{$account}";
        $ttl = $this->calculateTtl($tokenData);
        $this->cache->put($key, $tokenData, $ttl);
    }

    public function forget(string $account): void
    {
        $key = "bkash_token_{$account}";
        $this->cache->forget($key);
    }

    public function calculateTtl(array $tokenData): int
    {
        $expiresIn = $tokenData['expires_in'] ?? 3600;
        return max(60, (int) $expiresIn - $this->ttlBuffer);
    }
}