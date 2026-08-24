# Webhooks

bKash delivers real-time payment notifications via AWS SNS to a listener URL you provide during onboarding.

## Setup

1. Deploy your app with the webhook route: `https://yourdomain.com/bkash/webhook`
2. Share the URL with bKash technical support — they provision the subscription
3. bKash sends a `SubscriptionConfirmation` message; the package verifies it and confirms automatically

The webhook route is registered by `bkash:install`. It must be publicly reachable over HTTPS.

## What the package does per request

1. Parses the SNS envelope
2. Verifies the RSA-SHA1 signature against the signing certificate (host must end in `.amazonaws.com`)
3. Rejects duplicate `MessageId`s (SNS is at-least-once delivery)
4. Dispatches events

## Events

| Event | Fired when |
|---|---|
| `WebhookReceived` | every valid notification (raw payload) |
| `PaymentCompleted` | inner `transactionStatus = Completed` |
| `PaymentFailed` | inner status `Failed` / `Cancelled` |
| `RefundCompleted` | inner `transactionType = refund` |

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Tiash\LaravelBkash\Events\PaymentCompleted::class => [
        \App\Listeners\FulfillOrder::class,
    ],
];
```

## Payload

```php
class HandleWebhook
{
    public function handle(WebhookReceived $event): void
    {
        $data = $event->data;

        $data['trxID'];                  // e.g. 4J420ANOXC
        $data['amount'];
        $data['transactionStatus'];
        $data['transactionType'];        // see constants below
        $data['transactionReference'];   // user-provided reference
        $data['merchantInvoiceNumber'];
    }
}
```

Only successfully completed payments are notified. For more detail, call `Bkash::payment()->search($trxId)`.

## Transaction types

```php
use Tiash\LaravelBkash\Events\WebhookEvent;

WebhookEvent::PAYMENT_API;       // 10002294 — payment via API
WebhookEvent::PAYMENT_QR;        // 10003126 — payment via QR
WebhookEvent::PAYMENT_USSD;      // 10002175 — payment via *247#
WebhookEvent::PAYMENT_BANK;      // 10003476 — payment via bank
WebhookEvent::REDEEM_VOUCHER;    // 10002809
WebhookEvent::M2M_TRANSFER_API;  // 10002264

WebhookEvent::isPayment($type);          // bool
WebhookEvent::getDescription($type);     // "Payment via API"
```

## Coupon payments

Coupon transactions carry extra fields in the message:

- `couponAmount`
- `merchantShareAmount`
- `saleAmount` (original amount before coupon)

## Invalid requests

Requests failing verification return HTTP 400 with the reason. Nothing is dispatched.
