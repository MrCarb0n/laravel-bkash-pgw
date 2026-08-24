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

        // Use atomic add() - only succeeds if key doesn't exist
        if (!$this->cache->add($lockKey, true, $this->ttl)) {
            throw new \RuntimeException("Idempotency key {$key} already used");
        }

        try {
            return $callback();
        } catch (\Throwable $e) {
            // On failure, remove lock so it can be retried
            $this->cache->forget($lockKey);
            throw $e;
        }
    }

    public function checkAndMark(string $key): bool
    {
        $lockKey = "bkash_dedup_{$key}";

        // Atomic check-and-mark using add()
        return $this->cache->add($lockKey, true, $this->ttl);
    }
}