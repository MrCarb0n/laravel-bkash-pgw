<?php

namespace Tiash\LaravelBkash\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tiash\LaravelBkash\BkashServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [BkashServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('bkash', [
            'sandbox'      => true,
            'api_version'  => 'v1.2.0-beta',
            'callback_url' => 'https://example.com/callback',
            'accounts' => [
                'default' => [
                    'app_key'    => 'test_app_key',
                    'app_secret' => 'test_app_secret',
                    'username'   => 'test_username',
                    'password'   => 'test_password',
                ],
            ],
            'cache' => [
                'store'      => 'array',
                'ttl_buffer' => 300,
            ],
            'http' => [
                'timeout'         => 30,
                'connect_timeout' => 10,
                'retry'           => 2,
            ],
            'log' => [
                'channel'    => null,
                'log_tokens' => false,
            ],
        ]);
    }
}