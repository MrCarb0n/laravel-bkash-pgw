<?php

namespace Tiash\LaravelBkash\Contracts;

interface BkashClientInterface
{
    public function post(string $endpoint, array $payload, array $headers = []): array;
    public function get(string $endpoint, array $headers = []): array;
}