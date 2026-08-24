<?php

namespace Tiash\LaravelBkash\Api;

use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Http\HeaderFactory;

class AgreementApi
{
    private $client;
    private $tokenManager;
    private $credentials;
    private $baseUrl;
    private $callbackUrl;

    public function __construct(
        BkashClientInterface $client,
        TokenManager $tokenManager,
        Credentials $credentials,
        string $baseUrl,
        string $callbackUrl
    ) {
        $this->client       = $client;
        $this->tokenManager = $tokenManager;
        $this->credentials  = $credentials;
        $this->baseUrl      = rtrim($baseUrl, '/');
        $this->callbackUrl  = $callbackUrl;
    }

    /** Create an agreement (mandate) request; redirect payer to returned bkashURL. */
    public function create(array $data, string $account = 'default'): array
    {
        $payload = [
            'mode'           => '0001',
            'payerReference' => $data['payerReference'] ?? '',
            'callbackURL'    => $data['callbackURL'] ?? $this->callbackUrl,
        ];

        return $this->client->post($this->baseUrl . '/checkout/agreement/create', $payload, $this->authHeaders($account));
    }

    /** Finalize an agreement after payer confirmation. */
    public function execute(string $paymentId, string $account = 'default'): array
    {
        $payload = ['paymentID' => $paymentId];

        return $this->client->post($this->baseUrl . '/checkout/agreement/execute', $payload, $this->authHeaders($account));
    }

    /** Check the current status of an agreement. */
    public function status(string $agreementId, string $account = 'default'): array
    {
        $payload = ['agreementID' => $agreementId];

        return $this->client->post($this->baseUrl . '/checkout/agreement/status', $payload, $this->authHeaders($account));
    }

    private function authHeaders(string $account): array
    {
        return HeaderFactory::idToken(
            $this->tokenManager->getToken($account),
            $this->credentials->get($account)['app_key']
        );
    }
}