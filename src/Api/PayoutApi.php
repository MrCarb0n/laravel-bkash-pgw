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
    public function disburse(string $receiver, $amount, string $reference, string $currency = 'BDT', string $account = 'default'): array
    {
        $payload = [
            'receiverMSISDN'        => $receiver,
            'amount'                => Amount::format($amount),
            'currency'              => $currency,
            'merchantInvoiceNumber' => $reference,
        ];

        return $this->client->post(
            $this->baseUrl . '/checkout/payment/b2cPayment',
            $payload,
            $this->authHeaders($account)
        );
    }

    /** Send funds to another business wallet (B2B). Requires payoutID from bKash. */
    public function disburseB2B(string $payoutId, string $receiver, $amount, string $reference, string $currency = 'BDT', string $account = 'default'): array
    {
        $payload = [
            'payoutID'              => $payoutId,
            'receiverMSISDN'        => $receiver,
            'amount'                => Amount::format($amount),
            'currency'              => $currency,
            'merchantInvoiceNumber' => $reference,
        ];

        return $this->client->post(
            $this->baseUrl . '/tokenized/payout/b2b',
            $payload,
            $this->authHeaders($account)
        );
    }

    /** Initiate a B2B payout — returns payoutID required for disburseB2B. */
    public function initiateB2B(string $type = 'B2B', string $reference = '', string $account = 'default'): array
    {
        $payload = [
            'type' => $type,
        ];
        if ($reference !== '') {
            $payload['reference'] = $reference;
        }

        return $this->client->post(
            $this->baseUrl . '/tokenized/payout/initiate',
            $payload,
            $this->authHeaders($account)
        );
    }

    /** Query status of a B2B payout by payoutID. */
    public function queryB2B(string $payoutId, string $account = 'default'): array
    {
        $payload = ['payoutID' => $payoutId];

        return $this->client->post(
            $this->baseUrl . '/tokenized/payout/query',
            $payload,
            $this->authHeaders($account)
        );
    }

    /** Full B2B payout workflow: initiate -> disburse in one call. */
    public function disburseB2BWorkflow(
        string $receiver,
        $amount,
        string $reference,
        string $currency = 'BDT',
        string $type = 'B2B',
        string $initReference = '',
        string $account = 'default'
    ): array {
        $initiate = $this->initiateB2B($type, $initReference, $account);

        if (!isset($initiate['payoutID'])) {
            throw new \RuntimeException('Failed to initiate B2B payout: ' . ($initiate['statusMessage'] ?? 'Unknown error'));
        }

        return $this->disburseB2B($initiate['payoutID'], $receiver, $amount, $reference, $currency, $account);
    }

    private function authHeaders(string $account): array
    {
        return HeaderFactory::idToken(
            $this->tokenManager->getToken($account),
            $this->credentials->get($account)['app_key']
        );
    }
}