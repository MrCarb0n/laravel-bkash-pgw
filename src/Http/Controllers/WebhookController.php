<?php

namespace Tiash\LaravelBkash\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tiash\LaravelBkash\Events\PaymentCompleted;
use Tiash\LaravelBkash\Events\PaymentFailed;
use Tiash\LaravelBkash\Events\RefundCompleted;
use Tiash\LaravelBkash\Events\WebhookReceived;
use Tiash\LaravelBkash\Security\SnsSignatureVerifier;
use Throwable;

class WebhookController extends Controller
{
    private $verifier;

    public function __construct(SnsSignatureVerifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /** Receive bKash SNS webhook: verify RSA signature, dedup, dispatch domain events. */
    public function handle(Request $request)
    {
        try {
            $result = $this->verifier->verify($request->getContent());
        } catch (Throwable $e) {
            return response('Invalid webhook: ' . $e->getMessage(), 400);
        }

        if ($result['type'] === 'subscription_confirmation') {
            $this->confirmSubscription($result['subscribe_url']);
            return response('Subscribed', 200);
        }

        event(new WebhookReceived($result));

        $data   = $result['data'];
        $status = $data['transactionStatus'] ?? '';

        if ($status === 'Completed') {
            event(new PaymentCompleted($data));
        } elseif (in_array($status, ['Failed', 'Cancelled'], true)) {
            event(new PaymentFailed($data));
        }

        if (($data['transactionType'] ?? '') === 'refund') {
            event(new RefundCompleted($data));
        }

        return response('OK', 200);
    }

    private function confirmSubscription(string $subscribeUrl): void
    {
        if (str_starts_with($subscribeUrl, 'https://') && str_contains($subscribeUrl, '.amazonaws.com')) {
            @file_get_contents($subscribeUrl);
        }
    }
}