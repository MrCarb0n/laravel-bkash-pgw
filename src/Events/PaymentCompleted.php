<?php

namespace Tiash\LaravelBkash\Events;

class PaymentCompleted
{
    /** @var array */
    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}