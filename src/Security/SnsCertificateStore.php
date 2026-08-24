<?php

namespace Tiash\LaravelBkash\Security;

use Illuminate\Contracts\Cache\Repository;

class SnsCertificateStore
{
    private Repository $cache;
    private int $ttl;

    public function __construct(Repository $cache, int $ttl = 3600)
    {
        $this->cache = $cache;
        $this->ttl   = $ttl;
    }

    public function get(string $url): string
    {
        $key = 'bkash_sns_cert_' . md5($url);
        $cached = $this->cache->get($key);

        if ($cached) {
            return $cached;
        }

        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException("Failed to download SNS certificate from {$url}");
        }

        $this->cache->put($key, $content, $this->ttl);
        return $content;
    }
}