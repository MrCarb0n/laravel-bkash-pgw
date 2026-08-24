<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tiash\LaravelBkash\Api\TokenizedPaymentApi;
use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenCache;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Http\GuzzleClient;
use Tiash\LaravelBkash\Http\ResponseNormalizer;
use Tiash\LaravelBkash\Support\IdempotencyLock;
use Tiash\LaravelBkash\Tests\TestCase;

class TokenizedPaymentApiTest extends TestCase
{
    private $cacheStore;

    private function makeApi(MockHandler $handler): TokenizedPaymentApi
    {
        $this->cacheStore = $this->app['cache']->store('array');

        $client = new GuzzleClient(
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
            ['timeout' => 30, 'connect_timeout' => 10, 'retry' => 0],
            new ResponseNormalizer(),
            HandlerStack::create($handler)
        );

        $credentials = new Credentials([
            'default' => [
                'app_key'    => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'username'   => 'test_username',
                'password'   => 'test_password',
            ],
        ]);

        $tokenManager = new TokenManager(
            $client,
            new TokenCache($this->cacheStore),
            $credentials,
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'
        );

        return new TokenizedPaymentApi(
            $client,
            $tokenManager,
            $credentials,
            new IdempotencyLock($this->cacheStore),
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
            'https://example.com/bkash/callback'
        );
    }

    private function primeToken(): void
    {
        (new TokenCache($this->cacheStore))->put('default', [
            'id_token'      => 'cached_token',
            'refresh_token' => 'refresh_123',
            'expires_in'    => 3600,
        ]);
    }

    public function test_create_formats_amount_and_returns_response(): void
    {
        $handler = new MockHandler([
            new Response(200, [], json_encode([
                'statusCode'    => '0000',
                'paymentID'     => 'PAY123',
                'bkashURL'      => 'https://sandbox.payment.bka.sh/redirect?paymentID=PAY123',
                'amount'        => '100.00',
                'transactionStatus' => 'Initiated',
            ])),
        ]);

        $api = $this->makeApi($handler);
        $this->primeToken();

        $response = $api->create([
            'payerReference'        => 'ORDER123',
            'amount'                => 100,
            'merchantInvoiceNumber' => 'INV123',
        ]);

        $request = json_decode($handler->getLastRequest()->getBody(), true);
        $this->assertSame('100.00', $request['amount']);
        $this->assertSame('0011', $request['mode']);
        $this->assertSame('https://example.com/bkash/callback', $request['callbackURL']);
        $this->assertSame('PAY123', $response['paymentID']);

        $authHeader = $handler->getLastRequest()->getHeaderLine('Authorization');
        $this->assertSame('cached_token', $authHeader);
        $this->assertSame('test_app_key', $handler->getLastRequest()->getHeaderLine('X-App-Key'));
    }

    public function test_execute_is_idempotent_per_payment(): void
    {
        $handler = new MockHandler([
            new Response(200, [], json_encode([
                'statusCode'        => '0000',
                'transactionStatus' => 'Completed',
                'trxID'             => 'TRX123',
            ])),
        ]);

        $api = $this->makeApi($handler);
        $this->primeToken();

        $response = $api->execute('PAY123');
        $this->assertSame('Completed', $response['transactionStatus']);

        $this->expectException(\RuntimeException::class);
        $api->execute('PAY123');
    }

    public function test_query_posts_payment_id(): void
    {
        $handler = new MockHandler([
            new Response(200, [], json_encode([
                'paymentID'         => 'PAY123',
                'transactionStatus' => 'Completed',
            ])),
        ]);

        $api = $this->makeApi($handler);
        $this->primeToken();

        $response = $api->query('PAY123');

        $request = json_decode($handler->getLastRequest()->getBody(), true);
        $this->assertSame(['paymentID' => 'PAY123'], $request);
        $this->assertSame('Completed', $response['transactionStatus']);
    }

    public function test_search_posts_trx_id(): void
    {
        $handler = new MockHandler([
            new Response(200, [], json_encode([
                'trxID'             => 'TRX123',
                'transactionStatus' => 'Completed',
            ])),
        ]);

        $api = $this->makeApi($handler);
        $this->primeToken();

        $response = $api->search('TRX123');

        $request = json_decode($handler->getLastRequest()->getBody(), true);
        $this->assertSame(['trxID' => 'TRX123'], $request);
        $this->assertSame('Completed', $response['transactionStatus']);
    }
}
