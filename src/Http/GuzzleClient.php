<?php

namespace Tiash\LaravelBkash\Http;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Exceptions\NetworkException;

class GuzzleClient implements BkashClientInterface
{
    private $client;
    private ResponseNormalizer $normalizer;
    private int $retry;
    private int $retryDelay;

    public function __construct(string $baseUrl, array $httpConfig, ResponseNormalizer $normalizer, ?HandlerStack $handler = null)
    {
        $this->normalizer  = $normalizer;
        $this->retry       = $httpConfig['retry'] ?? 2;
        $this->retryDelay  = $httpConfig['retry_delay'] ?? 100000; // microseconds

        $options = [
            'base_uri'        => rtrim($baseUrl, '/') . '/',
            'timeout'         => $httpConfig['timeout'] ?? 30,
            'connect_timeout' => $httpConfig['connect_timeout'] ?? 10,
            'http_errors'     => false,
        ];

        if ($handler !== null) {
            $options['handler'] = $handler;
        }

        $this->client = new GuzzleHttpClient($options);
    }

    /** @inheritDoc */
    public function post(string $endpoint, array $payload, array $headers = []): array
    {
        return $this->request('POST', $endpoint, $payload, $headers);
    }

    /** @inheritDoc */
    public function get(string $endpoint, array $headers = []): array
    {
        return $this->request('GET', $endpoint, null, $headers);
    }

    private function request(string $method, string $endpoint, ?array $payload, array $headers): array
    {
        $options = ['headers' => $headers];
        if ($payload !== null) {
            $options['json'] = $payload;
        }

        // Retry on GET and idempotent POST endpoints
        $idempotentEndpoints = [
            '/checkout/execute',
            '/checkout/payment/status',
            '/checkout/general/searchTransaction',
            '/checkout/agreement/status',
        ];
        $isIdempotent = $method === 'GET' || in_array($endpoint, $idempotentEndpoints, true);

        $maxAttempts = $isIdempotent ? $this->retry + 1 : 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->client->request($method, $endpoint, $options);
                return $this->normalizer->normalize($response);
            } catch (GuzzleException $e) {
                if ($attempt >= $maxAttempts) {
                    throw new NetworkException(
                        "bKash {$method} {$endpoint} failed: {$e->getMessage()}",
                        0,
                        $e
                    );
                }
                usleep($this->retryDelay * $attempt);
            }
        }

        throw new NetworkException("bKash request exhausted retries");
    }
}