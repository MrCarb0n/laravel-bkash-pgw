<?php

namespace Tiash\LaravelBkash\Security;

class CallbackSignature
{
    public static function validate(array $query, string $appSecret): bool
    {
        if (!isset($query['signature'])) {
            return false;
        }

        $signature = $query['signature'];
        unset($query['signature']);

        ksort($query);
        $payload = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $expected = hash_hmac('sha256', $payload, $appSecret, true);
        $expected = base64_encode($expected);

        return hash_equals($signature, $expected);
    }
}