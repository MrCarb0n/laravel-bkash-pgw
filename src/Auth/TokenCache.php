<?php

namespace Tiash\LaravelBkash\Auth;

use Illuminate\Contracts\Cache\Repository;

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

    /** Simple lock for token acquisition (boolean value, short TTL). */
    public function lock(string $key, int $ttl = 10): bool
    {
        $lockKey = "bkash_token_lock_{$key}";
        // add() only sets if key doesn't exist (atomic in most cache drivers)
        return $this->cache->add($lockKey, true, $ttl);
    }

    public function unlock(string $key): void
    {
        $this->cache->forget("bkash_token_lock_{$key}");
    }
}