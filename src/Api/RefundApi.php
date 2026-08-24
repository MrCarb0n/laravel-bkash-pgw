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
    private $baseUrl;

    public function __construct(
        BkashClientInterface $client,
        TokenManager $tokenManager,
        Credentials $credentials,
        string $baseUrl
    ) {
        $this->client       = $client;
        $this->tokenManager = $tokenManager;
        $this->credentials  = $credentials;
        $this->baseUrl      = rtrim($baseUrl, '/');
    }

    /** Reverse a completed transaction, fully or partially. */
    public function refund(
        string $paymentId,
        string $trxId,
        $amount,
        string $reason = 'Refund',
        string $sku = 'REF',
        string $account = 'default'
    ): array {
        $payload = [
            'paymentID' => $paymentId,
            'trxID'     => $trxId,
            'amount'    => Amount::format($amount),
            'reason'    => $reason,
            'sku'       => $sku,
        ];

        return $this->client->post($this->baseUrl . '/checkout/payment/refund', $payload, $this->authHeaders($account));
    }

    /** Check status of a previous refund without re-triggering it. */
    public function status(string $paymentId, string $trxId, string $account = 'default'): array
    {
        $payload = [
            'paymentID' => $paymentId,
            'trxID'     => $trxId,
        ];

        return $this->client->post(
            $this->baseUrl . '/checkout/payment/refund/status',
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