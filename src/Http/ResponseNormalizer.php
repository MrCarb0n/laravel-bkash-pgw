<?php

namespace Tiash\LaravelBkash\Http;

use Psr\Http\Message\ResponseInterface;
use Tiash\LaravelBkash\Exceptions\ApiException;
use Tiash\LaravelBkash\Exceptions\ErrorCodeMap;
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

        // Handle 4xx client errors
        if ($statusCode >= 400) {
            $message = "bKash HTTP {$statusCode}";
            $data = [];

            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                    $message = $decoded['errorMessage'] ?? $decoded['errorMessageEn'] ?? $decoded['statusMessage'] ?? $message;
                }
            }

            $errorCode = $data['errorCode'] ?? $data['statusCode'] ?? (string) $statusCode;
            if (ErrorCodeMap::has($errorCode)) {
                $message = ErrorCodeMap::getMessage($errorCode);
            }

            throw new ApiException($message, $errorCode, $data);
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
                $errorCode = $data['errorCode'] ?? $data['statusCode'] ?? 'UNKNOWN';
                $message = $data['errorMessage'] ?? $data['errorMessageEn'] ?? $data['statusMessage'] ?? 'bKash API error';

                if (ErrorCodeMap::has($errorCode)) {
                    $message = ErrorCodeMap::getMessage($errorCode);
                }

                throw new ApiException(
                    $message,
                    $errorCode,
                    $data
                );
            }
        }

        return $data;
    }
}