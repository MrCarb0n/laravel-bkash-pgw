<?php

namespace Tiash\LaravelBkash\Http;

use Psr\Http\Message\ResponseInterface;
use Tiash\LaravelBkash\Exceptions\ApiException;
use Tiash\LaravelBkash\Exceptions\NetworkException;

class ResponseNormalizer
{
    public function normalize(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 500) {
            throw new NetworkException("bKash HTTP {$statusCode}: {$body}");
        }

        if ($body === '') {
            throw new NetworkException("bKash HTTP {$statusCode}: empty response body");
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new NetworkException('bKash returned invalid JSON: ' . substr($body, 0, 200));
        }

        if (isset($data['errorCode']) || isset($data['statusCode'])) {
            if (($data['errorCode'] ?? null) !== '0000' && ($data['statusCode'] ?? null) !== '0000') {
                throw new ApiException(
                    $data['errorMessage'] ?? $data['statusMessage'] ?? 'bKash API error',
                    $data['errorCode'] ?? $data['statusCode'] ?? 'UNKNOWN',
                    $data
                );
            }
        }

        return $data;
    }
}