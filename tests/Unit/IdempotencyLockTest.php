<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tiash\LaravelBkash\Support\IdempotencyLock;

class IdempotencyLockTest extends TestCase
{
    private $cache;
    private $lock;

    protected function setUp(): void
    {
        $arrayStore = new \Illuminate\Cache\ArrayStore();
        $this->cache = new \Illuminate\Cache\Repository($arrayStore);
        $this->lock  = new IdempotencyLock($this->cache, 60);
    }

    public function test_runs_callback_once(): void
    {
        $result = $this->lock->executeOnce('key1', fn () => 'value');
        $this->assertSame('value', $result);
    }

    public function test_blocks_second_execution_of_same_key(): void
    {
        $this->lock->executeOnce('key2', fn () => 'first');

        $this->expectException(\RuntimeException::class);
        $this->lock->executeOnce('key2', fn () => 'second');
    }

    public function test_releases_lock_when_callback_throws(): void
    {
        try {
            $this->lock->executeOnce('key3', function () {
                throw new \DomainException('bKash unreachable');
            });
            $this->fail('Expected DomainException');
        } catch (\DomainException $e) {
            // expected — lock must be released so caller may retry
        }

        $result = $this->lock->executeOnce('key3', fn () => 'retry-ok');
        $this->assertSame('retry-ok', $result);
    }
}