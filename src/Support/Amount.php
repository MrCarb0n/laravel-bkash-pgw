<?php

namespace Tiash\LaravelBkash\Support;

class Amount
{
    public static function format(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public static function validate(float|int|string $amount): bool
    {
        $formatted = self::format($amount);
        return (float) $formatted > 0;
    }
}