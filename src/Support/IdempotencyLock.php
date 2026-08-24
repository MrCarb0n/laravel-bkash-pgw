<?php

namespace Tiash\LaravelBkash\Support;

use Illuminate\Contracts\Cache\Repository;

class IdempotencyLock
{
    private Repository $cache;
    private int $ttl;

    public function __construct(Repository $cache, int $ttl = 300)
    {
        $this->cache = $cache;
        $this->ttl   = $ttl;
    }

    public function executeOnce(string $key, callable $callback): mixed
    {
        $lockKey = "bkash_idempotent_{$key}";

        if ($this->cache->has($lockKey)) {
            throw new \RuntimeException("Idempotency key {$key} already used");
        }

        $this->cache->put($lockKey, true, $this->ttl);

        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->cache->forget($lockKey);
            throw $e;
        }
    }

    public function checkAndMark(string $key): bool
    {
        $lockKey = "bkash_dedup_{$key}";

        if ($this->cache->has($lockKey)) {
            return false;
        }

        $this->cache->put($lockKey, true, $this->ttl);
        return true;
    }
}