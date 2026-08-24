<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use Tiash\LaravelBkash\Auth\TokenCache;
use Tiash\LaravelBkash\Tests\TestCase;

class TokenCacheTest extends TestCase
{
    public function test_stores_and_retrieves_token(): void
    {
        $cache = new TokenCache($this->app['cache']->store('array'));

        $this->assertNull($cache->get('default'));

        $token = ['id_token' => 'abc', 'refresh_token' => 'r1', 'expires_in' => 3600];
        $cache->put('default', $token);

        $this->assertSame($token, $cache->get('default'));

        $cache->forget('default');
        $this->assertNull($cache->get('default'));
    }

    public function test_ttl_is_expires_in_minus_buffer_with_floor(): void
    {
        $cache = new TokenCache($this->app['cache']->store('array'), 300);

        $this->assertSame(3300, $cache->calculateTtl(['expires_in' => 3600]));
        $this->assertSame(60, $cache->calculateTtl(['expires_in' => 100]));
        $this->assertSame(3300, $cache->calculateTtl([])); // default 3600 - buffer
    }

    public function test_ignores_malformed_cached_data(): void
    {
        $store = $this->app['cache']->store('array');
        $store->put('bkash_token_default', 'garbage', 60);
        $cache = new TokenCache($store);

        $this->assertNull($cache->get('default'));
    }
}