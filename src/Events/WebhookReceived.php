<?php

namespace Tiash\LaravelBkash\Events;

class WebhookReceived
{
    /** @var array */
    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}