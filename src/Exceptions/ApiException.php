<?php

namespace Tiash\LaravelBkash\Exceptions;

class ApiException extends BkashException
{
    private string $errorCode;
    private array $rawResponse;

    public function __construct(string $message, string $errorCode, array $rawResponse = [])
    {
        $code = is_numeric($errorCode) ? (int) $errorCode : 0;
        parent::__construct($message, $code);
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