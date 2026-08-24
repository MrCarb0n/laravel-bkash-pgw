<?php

namespace Tiash\LaravelBkash\Api;

use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Http\HeaderFactory;
use Tiash\LaravelBkash\Support\Amount;
use Tiash\LaravelBkash\Support\IdempotencyLock;

class TokenizedPaymentApi
{
    public const MODE_CHECKOUT = '0011';
    public const MODE_AGREEMENT_PAYMENT = '0001';

    private $client;
    private $tokenManager;
    private $credentials;
    private $idempotency;
    private $baseUrl;
    private $callbackUrl;

    public function __construct(
        BkashClientInterface $client,
        TokenManager $tokenManager,
        Credentials $credentials,
        IdempotencyLock $idempotency,
        string $baseUrl,
        string $callbackUrl
    ) {
        $this->client       = $client;
        $this->tokenManager = $tokenManager;
        $this->credentials  = $credentials;
        $this->idempotency  = $idempotency;
        $this->baseUrl      = rtrim($baseUrl, '/');
        $this->callbackUrl  = $callbackUrl;
    }

    /** Create a tokenized payment; redirect the payer to the returned bkashURL. */
    public function create(array $data, string $account = 'default'): array
    {
        $payload = [
            'mode'                  => $data['mode'] ?? self::MODE_CHECKOUT,
            'payerReference'        => $data['payerReference'] ?? '',
            'callbackURL'           => $data['callbackURL'] ?? $this->callbackUrl,
            'amount'                => Amount::format($data['amount'] ?? 0),
            'currency'              => $data['currency'] ?? 'BDT',
            'intent'                => $data['intent'] ?? 'sale',
            'merchantInvoiceNumber' => $data['merchantInvoiceNumber'] ?? '',
        ];

        if (!empty($data['agreementID'])) {
            $payload['agreementID'] = $data['agreementID'];
        }

        return $this->client->post($this->baseUrl . '/checkout/create', $payload, $this->authHeaders($account));
    }

    /** Finalize a payment. Guarded: bKash allows exactly one execution per paymentID. */
    public function execute(string $paymentId, string $account = 'default'): array
    {
        return $this->idempotency->executeOnce(
            "execute_{$paymentId}",
            function () use ($paymentId, $account) {
                $payload = ['paymentID' => $paymentId];

                return $this->client->post(
                    $this->baseUrl . '/checkout/execute',
                    $payload,
                    $this->authHeaders($account)
                );
            }
        );
    }

    /** Get current status of an initiated/executed payment by paymentID. */
    public function query(string $paymentId, string $account = 'default'): array
    {
        $payload = ['paymentID' => $paymentId];

        return $this->client->post(
            $this->baseUrl . '/checkout/payment/status',
            $payload,
            $this->authHeaders($account)
        );
    }

    /** Look up a completed transaction by its bKash trxID. */
    public function search(string $trxId, string $account = 'default'): array
    {
        $payload = ['trxID' => $trxId];

        return $this->client->post(
            $this->baseUrl . '/checkout/general/searchTransaction',
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