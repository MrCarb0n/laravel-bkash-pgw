<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Exceptions\ApiException;
use Tiash\LaravelBkash\Tests\TestCase;

class CredentialsTest extends TestCase
{
    private function credentials(array $accounts): Credentials
    {
        return new Credentials($accounts);
    }

    public function test_returns_account_when_complete(): void
    {
        $creds = $this->credentials([
            'default' => [
                'app_key'    => 'k',
                'app_secret' => 's',
                'username'   => 'u',
                'password'   => 'p',
            ],
        ]);

        $this->assertSame('k', $creds->get('default')['app_key']);
    }

    public function test_throws_for_unknown_account(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('[nope]');

        $this->credentials([])->get('nope');
    }

    public function test_throws_for_missing_key(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('accounts.default.app_key');

        $this->credentials([
            'default' => ['app_key' => '', 'app_secret' => 's', 'username' => 'u', 'password' => 'p'],
        ])->get('default');
    }
}