<?php

namespace Tiash\LaravelBkash\Exceptions;

class ApiException extends BkashException
{
    private string $errorCode;
    private array $rawResponse;

    public function __construct(string $message, string $errorCode, array $rawResponse = [])
    {
        parent::__construct($message);
        $this->errorCode   = $errorCode;
        $this->rawResponse = $rawResponse;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }
}