<?php

namespace Tiash\LaravelBkash\Support;

class CallbackUrlBuilder
{
    public static function build(string $baseCallbackUrl, string $paymentId, string $status): string
    {
        $separator = parse_url($baseCallbackUrl, PHP_URL_QUERY) ? '&' : '?';
        return "{$baseCallbackUrl}{$separator}paymentID={$paymentId}&status={$status}&signature=" . self::generateSignature($paymentId, $status);
    }

    public static function success(string $baseCallbackUrl, string $paymentId): string
    {
        return self::build($baseCallbackUrl, $paymentId, 'success');
    }

    public static function failure(string $baseCallbackUrl, string $paymentId): string
    {
        return self::build($baseCallbackUrl, $paymentId, 'failure');
    }

    public static function cancel(string $baseCallbackUrl, string $paymentId): string
    {
        return self::build($baseCallbackUrl, $paymentId, 'cancel');
    }

    private static function generateSignature(string $paymentId, string $status): string
    {
        return ''; // bKash generates this server-side; kept for documentation
    }
}