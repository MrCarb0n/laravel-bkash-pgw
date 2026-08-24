<?php

namespace Tiash\LaravelBkash\Api;

use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Http\HeaderFactory;
use Tiash\LaravelBkash\Support\Amount;

class PayoutApi
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

    /** Send funds real-time to a beneficiary wallet (B2C). */
    public function disburse(string $receiver, $amount, string $reference, string $account = 'default'): array
    {
        $payload = [
            'receiver'  => $receiver,
            'amount'    => Amount::format($amount),
            'reference' => $reference,
        ];

        return $this->client->post(
            $this->baseUrl . '/checkout/payment/b2cPayment',
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