<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tiash\LaravelBkash\Api\AgreementApi;
use Tiash\LaravelBkash\Api\PayoutApi;
use Tiash\LaravelBkash\Api\RefundApi;
use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenCache;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Http\GuzzleClient;
use Tiash\LaravelBkash\Http\ResponseNormalizer;
use Tiash\LaravelBkash\Tests\TestCase;

/** Request contracts pinned to https://developer.bka.sh */
class ApiContractsTest extends TestCase
{
    private $cacheStore;
    private $handler;

    private function makeClient(): GuzzleClient
    {
        $this->cacheStore = $this->app['cache']->store('array');
        $this->handler = new MockHandler();

        return new GuzzleClient(
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
            ['timeout' => 30, 'connect_timeout' => 10, 'retry' => 0],
            new ResponseNormalizer(),
            HandlerStack::create($this->handler)
        );
    }

    private function credentials(): Credentials
    {
        return new Credentials([
            'default' => [
                'app_key'    => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'username'   => 'test_username',
                'password'   => 'test_password',
            ],
        ]);
    }

    private function makeTokenManager(GuzzleClient $client): TokenManager
    {
        return new TokenManager(
            $client,
            new TokenCache($this->cacheStore),
            $this->credentials(),
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'
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

    private function makeRefundApi(GuzzleClient $client): RefundApi
    {
        return new RefundApi($client, $this->makeTokenManager($client), $this->credentials(), 'https://tokenized.sandbox.bka.sh');
    }

    private function makeAgreementApi(GuzzleClient $client): AgreementApi
    {
        return new AgreementApi(
            $client,
            $this->makeTokenManager($client),
            $this->credentials(),
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
            'https://example.com/bkash/callback'
        );
    }

    public function test_refund_posts_to_v2_endpoint(): void
    {
        $client = $this->makeClient();
        $api = $this->makeRefundApi($client);
        $this->primeToken();
        $this->handler->append(new Response(200, [], json_encode([
            'originalTrxId'           => 'TRX1',
            'refundTrxId'             => 'R1',
            'refundTransactionStatus' => 'Completed',
        ])));

        $api->refund('PAY1', 'TRX1', 50, 'reason', 'SKU1');

        $last = $this->handler->getLastRequest();
        $this->assertSame('/v2/tokenized-checkout/refund/payment/transaction', $last->getUri()->getPath());

        $body = json_decode((string) $last->getBody(), true);
        $this->assertSame('PAY1', $body['paymentId']);
        $this->assertSame('TRX1', $body['trxId']);
        $this->assertSame('50.00', $body['refundAmount']);
    }

    public function test_refund_status_posts_to_v2_endpoint(): void
    {
        $client = $this->makeClient();
        $api = $this->makeRefundApi($client);
        $this->primeToken();
        $this->handler->append(new Response(200, [], json_encode([
            'originalTrxId'      => 'TRX1',
            'refundTransactions' => [],
        ])));

        $api->status('PAY1', 'TRX1');

        $last = $this->handler->getLastRequest();
        $this->assertSame('/v2/tokenized-checkout/refund/payment/status', $last->getUri()->getPath());

        $body = json_decode((string) $last->getBody(), true);
        $this->assertSame(['paymentId' => 'PAY1', 'trxId' => 'TRX1'], $body);
    }

    public function test_agreement_create_uses_mode_0000(): void
    {
        $client = $this->makeClient();
        $api = $this->makeAgreementApi($client);
        $this->primeToken();
        $this->handler->append(new Response(200, [], json_encode([
            'statusCode' => '0000',
            'paymentID'  => 'PAY1',
            'bkashURL'   => 'https://sandbox.payment.bka.sh/redirect?paymentID=PAY1',
        ])));

        $api->create(['payerReference' => '01712345678']);

        $last = $this->handler->getLastRequest();
        $this->assertSame('/v1.2.0-beta/tokenized/checkout/create', $last->getUri()->getPath());

        $body = json_decode((string) $last->getBody(), true);
        $this->assertSame('0000', $body['mode']);
    }

    public function test_agreement_execute_uses_checkout_execute(): void
    {
        $client = $this->makeClient();
        $api = $this->makeAgreementApi($client);
        $this->primeToken();
        $this->handler->append(new Response(200, [], json_encode([
            'statusCode'      => '0000',
            'agreementID'     => 'AGR1',
            'agreementStatus' => 'Completed',
        ])));

        $api->execute('PAY1');

        $last = $this->handler->getLastRequest();
        $this->assertSame('/v1.2.0-beta/tokenized/checkout/execute', $last->getUri()->getPath());
    }

    private function makePayoutApi(GuzzleClient $client): PayoutApi
    {
        return new PayoutApi(
            $client,
            $this->makeTokenManager($client),
            $this->credentials(),
            'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
            'https://checkout.sandbox.bka.sh/v1.2.0-beta'
        );
    }

    public function test_b2c_posts_to_checkout_host(): void
    {
        $client = $this->makeClient();
        $api = $this->makePayoutApi($client);
        $this->primeToken();
        $this->handler->append(new Response(200, [], json_encode([
            'trxID'             => 'TRX1',
            'transactionStatus' => 'Completed',
        ])));

        $api->disburse('01700000000', 500, 'INV1');

        $last = $this->handler->getLastRequest();
        $this->assertSame('checkout.sandbox.bka.sh', $last->getUri()->getHost());
        $this->assertSame('/v1.2.0-beta/checkout/payment/b2cPayment', $last->getUri()->getPath());
    }

    public function test_b2b_paths_have_no_doubled_tokenized_segment(): void
    {
        $client = $this->makeClient();
        $api = $this->makePayoutApi($client);
        $this->primeToken();

        $this->handler->append(new Response(200, [], json_encode([
            'payoutID' => 'PO1', 'statusCode' => '0000',
        ])));
        $api->initiateB2B();
        $this->assertSame(
            '/v1.2.0-beta/tokenized/payout/initiate',
            $this->handler->getLastRequest()->getUri()->getPath()
        );

        $this->handler->append(new Response(200, [], json_encode([
            'trxID' => 'TRX1', 'transactionStatus' => 'Completed',
        ])));
        $api->disburseB2B('PO1', '01700000001', 1000, 'INV1');
        $this->assertSame(
            '/v1.2.0-beta/tokenized/payout/b2b',
            $this->handler->getLastRequest()->getUri()->getPath()
        );

        $this->handler->append(new Response(200, [], json_encode(['transactionStatus' => 'Initiated'])));
        $api->queryB2B('PO1');
        $this->assertSame(
            '/v1.2.0-beta/tokenized/payout/query',
            $this->handler->getLastRequest()->getUri()->getPath()
        );
    }

    public function test_refresh_token_sends_full_credentials(): void
    {
        $client = $this->makeClient();
        $tokens = $this->makeTokenManager($client);
        $this->primeToken();
        $this->handler->append(new Response(200, [], json_encode([
            'id_token'      => 'new_token',
            'refresh_token' => 'refresh_456',
            'expires_in'    => 3600,
        ])));

        $token = $tokens->refreshToken();

        $this->assertSame('new_token', $token);

        $last = $this->handler->getLastRequest();
        $body = json_decode((string) $last->getBody(), true);
        $this->assertSame('test_app_key', $body['app_key']);
        $this->assertSame('test_app_secret', $body['app_secret']);
        $this->assertSame('refresh_123', $body['refresh_token']);
        $this->assertSame('test_username', $last->getHeaderLine('username'));
    }
}
