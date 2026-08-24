# laravel-bkash-pgw

bKash Tokenized Checkout integration for Laravel — payments, refunds, agreements, payouts, webhooks.

[![Packagist Version](https://img.shields.io/packagist/v/mrcarb0n/laravel-bkash-pgw.svg)](https://packagist.org/packages/mrcarb0n/laravel-bkash-pgw)
[![Packagist Downloads](https://img.shields.io/packagist/dt/mrcarb0n/laravel-bkash-pgw.svg)](https://packagist.org/packages/mrcarb0n/laravel-bkash-pgw)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-8892BF.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-6%2B-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg)](https://phpstan.org)

## Features

- **Tokenized Checkout** — create, execute, query, search payments (modes 0011 + 0001)
- **Refunds** — refund + refund status, up to 10 partial refunds per transaction
- **Agreements** — create/execute/status saved-wallet agreements
- **B2C Payouts** — instant disbursement to beneficiaries
- **B2B Payouts** — initiate/disburse/query workflow
- **Webhooks** — AWS SNS signature verification (RSA-SHA1), auto-subscription, event dispatch
- **Callbacks** — HMAC-SHA256 signature validation on redirect callbacks
- **Token cache** — one grant token per account, auto-refresh before expiry
- **Idempotency** — execute-once guard + webhook deduplication
- **Config validation** — missing production credentials throw at boot
- **Amount formatting** — sends `"2300.00"` instead of `"2300.0"`
- **Error codes** — 150+ bKash error codes mapped to readable messages

## Requirements

- PHP 7.4+
- Laravel 6+
- Guzzle 7+
- bKash merchant account ([developer.bka.sh](https://developer.bka.sh))

## Installation

```bash
composer require mrcarb0n/laravel-bkash-pgw
php artisan bkash:install
```

`bkash:install` publishes `config/bkash.php`, `routes/bkash.php`, and the success/failed blade views.

Add your credentials to `.env`:

```env
BKASH_SANDBOX=true
BKASH_APP_KEY=your_sandbox_app_key
BKASH_APP_SECRET=your_sandbox_app_secret
BKASH_USERNAME=your_sandbox_username
BKASH_PASSWORD=your_sandbox_password
BKASH_CALLBACK_URL=https://yourdomain.com/bkash/callback
```

Sandbox credentials come from [developer.bka.sh](https://developer.bka.sh) (Tokenized Checkout → Sandbox).

Verify connectivity:

```bash
php artisan bkash:test-sandbox
```

```
1/3 Granting token...    OK (eyJraWQiOiJv...)
2/3 Creating payment...  OK (paymentID TR0011xxOTmQW...)
3/3 Querying payment...  OK (status: Initiated)
```

## Usage

### Create a payment

```php
use Tiash\LaravelBkash\Facades\Bkash;

$response = Bkash::payment()->create([
    'mode'                  => '0011',   // 0011 = checkout, 0001 = agreement payment
    'payerReference'        => 'ORDER_123',
    'callbackURL'           => route('bkash.callback'),
    'amount'                => 100.00,
    'currency'              => 'BDT',
    'intent'                => 'sale',
    'merchantInvoiceNumber' => 'INV_123',
]);

return redirect($response['bkashURL']);
```

The `/bkash/callback` route is registered automatically. It verifies the signature, executes the payment on `status=success`, and fires events.

### Listen for results

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Tiash\LaravelBkash\Events\PaymentCompleted::class => [
        \App\Listeners\FulfillOrder::class,
    ],
];
```

```php
class FulfillOrder
{
    public function handle(PaymentCompleted $event): void
    {
        $data = $event->data;

        Order::where('invoice_number', $data['merchantInvoiceNumber'])
            ->update(['status' => 'paid', 'trx_id' => $data['trxID']]);
    }
}
```

Events: `PaymentCompleted`, `PaymentFailed`, `RefundCompleted`, `WebhookReceived`.

### Refunds

```php
Bkash::refund()->refund($paymentId, $trxId, 50.00, 'Customer request');
Bkash::refund()->status($paymentId, $trxId);
```

### Agreements

```php
$agreement = Bkash::agreement()->create([
    'payerReference' => '01712345678',
    'callbackURL'    => route('bkash.callback'),
]);

$executed = Bkash::agreement()->execute($paymentId);
$status   = Bkash::agreement()->status($agreementId);
```

### Payouts

```php
// B2C — send money to a customer wallet
Bkash::payout()->disburse('01700000000', 500.00, 'INV-12345');

// B2B — initiate + disburse in one call
Bkash::payout()->disburseB2BWorkflow('01700000001', 1000.00, 'INV-12345');

// B2B step by step
$initiate = Bkash::payout()->initiateB2B();
$payoutId = $initiate['payoutID'];
Bkash::payout()->disburseB2B($payoutId, '01700000001', 1000.00, 'INV-12345');
Bkash::payout()->queryB2B($payoutId);
```

### Multiple accounts

```php
// config/bkash.php
'accounts' => [
    'default'   => [...],
    'secondary' => [...],
],
```

```php
Bkash::payment()->create($data, 'secondary');
Bkash::refund()->refund($pid, $tid, 10, 'reason', 'sku', 'secondary');
```

## Webhooks

Point bKash at `https://yourdomain.com/bkash/webhook` during onboarding. The package verifies each SNS message (RSA-SHA1, certificate from `.amazonaws.com`) and deduplicates by MessageId.

Transaction type constants:

```php
use Tiash\LaravelBkash\Events\WebhookEvent;

WebhookEvent::PAYMENT_API;      // 10002294
WebhookEvent::PAYMENT_QR;       // 10003126
WebhookEvent::PAYMENT_USSD;     // 10002175
WebhookEvent::PAYMENT_BANK;     // 10003476
WebhookEvent::REDEEM_VOUCHER;   // 10002809
WebhookEvent::M2M_TRANSFER_API; // 10002264
WebhookEvent::isPayment($type); // bool
WebhookEvent::getDescription($type);
```

## Error handling

```php
use Tiash\LaravelBkash\Exceptions\ApiException;
use Tiash\LaravelBkash\Exceptions\NetworkException;
use Tiash\LaravelBkash\Exceptions\SignatureException;

try {
    $response = Bkash::payment()->create([...]);
} catch (ApiException $e) {
    $e->getErrorCode();    // e.g. '2023'
    $e->getMessage();      // "Insufficient Balance"
    $e->getRawResponse();  // full response array
} catch (NetworkException $e) {
    // HTTP 5xx, timeout, empty body, invalid JSON
} catch (SignatureException $e) {
    // bad callback/webhook signature
}
```

Unknown error codes fall back to bKash's own message.

## Going live

1. Set `BKASH_SANDBOX=false` and swap in production credentials
2. Whitelist your server IP with bKash
3. Use HTTPS for callback and webhook URLs
4. Share the webhook URL with bKash support

## Testing

```bash
composer test          # 35 tests
vendor/bin/phpstan analyse --no-progress
```

## License

MIT — MrCarb0n