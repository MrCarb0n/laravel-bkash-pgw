<?php

namespace Tiash\LaravelBkash\Http;

class HeaderFactory
{
    public static function grant(string $username, string $password): array
    {
        return [
            'Content-Type' => 'application/json',
            'username'     => $username,
            'password'     => $password,
        ];
    }

    public static function idToken(string $idToken, string $appKey): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => $idToken,
            'X-App-Key'     => $appKey,
        ];
    }
}