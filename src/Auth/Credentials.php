<?php

namespace Tiash\LaravelBkash\Auth;

use Illuminate\Support\Arr;
use Tiash\LaravelBkash\Exceptions\ApiException;

class Credentials
{
    private array $accounts;

    public function __construct(array $accounts)
    {
        $this->accounts = $accounts;
    }

    public function get(string $account = 'default'): array
    {
        if (!$this->has($account)) {
            throw new ApiException("bKash account [{$account}] not configured", 'ACCOUNT_NOT_FOUND');
        }

        $config = $this->accounts[$account];
        $required = ['app_key', 'app_secret', 'username', 'password'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new ApiException("Missing required config: accounts.{$account}.{$key}", 'MISSING_CONFIG');
            }
        }

        return $config;
    }

    public function all(): array
    {
        return $this->accounts;
    }

    public function has(string $account): bool
    {
        return isset($this->accounts[$account]);
    }
}