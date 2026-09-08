<?php

namespace Tiash\LaravelBkash\Api;

use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Http\HeaderFactory;
use Tiash\LaravelBkash\Support\Amount;

class RefundApi
{
    private $client;
    private $tokenManager;
    private $credentials;
    private $host;

    public function __construct(
        BkashClientInterface $client,
        TokenManager $tokenManager,
        Credentials $credentials,
        string $host
    ) {
        $this->client       = $client;
        $this->tokenManager = $tokenManager;
        $this->credentials  = $credentials;
        $this->host         = rtrim($host, '/');
    }

    /** Reverse a completed transaction, fully or partially (up to 10 partial refunds).
     *  POST /v2/tokenized-checkout/refund/payment/transaction */
    public function refund(
        string $paymentId,
        string $trxId,
        $amount,
        string $reason = 'Refund',
        string $sku = 'REF',
        string $account = 'default'
    ): array {
        $payload = [
            'paymentId'    => $paymentId,
            'trxId'        => $trxId,
            'refundAmount' => Amount::format($amount),
            'sku'          => $sku,
            'reason'       => $reason,
        ];

        return $this->client->post($this->host . '/v2/tokenized-checkout/refund/payment/transaction', $payload, $this->authHeaders($account));
    }

    /** Check status of previous refunds without re-triggering them.
     *  POST /v2/tokenized-checkout/refund/payment/status */
    public function status(string $paymentId, string $trxId, string $account = 'default'): array
    {
        $payload = [
            'paymentId' => $paymentId,
            'trxId'     => $trxId,
        ];

        return $this->client->post(
            $this->host . '/v2/tokenized-checkout/refund/payment/status',
            $payload,
            $this->authHeaders($account)
        );
    }

    private function authHeaders(string $account): array
    {
        return HeaderFactory::idToken(
            $this->tokenManager->getToken($account),
            $this->credentials->get($account)['app_key']
        );
    }
}
