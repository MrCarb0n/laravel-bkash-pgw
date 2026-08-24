<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tiash\LaravelBkash\Api\TokenizedPaymentApi;
use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenCache;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Console\TestSandboxCommand;
use Tiash\LaravelBkash\Http\GuzzleClient;
use Tiash\LaravelBkash\Http\ResponseNormalizer;
use Tiash\LaravelBkash\Support\IdempotencyLock;
use Tiash\LaravelBkash\Tests\TestCase;

class TestSandboxCommandTest extends TestCase
{
    private function bindApi(MockHandler $handler): void
    {
        $store = $this->app['cache']->store('array');

        $client = new GuzzleClient(
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
            ['timeout' => 30, 'connect_timeout' => 10, 'retry' => 0],
            new ResponseNormalizer(),
            HandlerStack::create($handler)
        );

        $credentials = new Credentials(config('bkash.accounts'));

        $this->app->instance(TokenManager::class, new TokenManager(
            $client,
            new TokenCache($store),
            $credentials,
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'
        ));

        $this->app->instance(TokenizedPaymentApi::class, new TokenizedPaymentApi(
            $client,
            $this->app[TokenManager::class],
            $credentials,
            new IdempotencyLock($store),
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
            config('bkash.callback_url')
        ));
    }

    public function test_reports_success_when_all_steps_pass(): void
    {
        $this->bindApi(new MockHandler([
            new Response(200, [], json_encode(['id_token' => 'tok', 'refresh_token' => 'r', 'expires_in' => 3600])),
            new Response(200, [], json_encode(['statusCode' => '0000', 'paymentID' => 'PAY1', 'bkashURL' => 'https://pay'])),
            new Response(200, [], json_encode(['transactionStatus' => 'Initiated'])),
        ]));

        $this->artisan(TestSandboxCommand::class)->assertExitCode(0);
    }

    public function test_fails_gracefully_on_api_error(): void
    {
        $this->bindApi(new MockHandler([
            new Response(401, [], json_encode(['statusMessage' => 'Unauthorized'])),
        ]));

        $this->artisan(TestSandboxCommand::class)->assertExitCode(1);
    }

    public function test_refuses_to_run_against_production(): void
    {
        config(['bkash.sandbox' => false]);

        $this->artisan(TestSandboxCommand::class)->assertExitCode(1);
    }
}
