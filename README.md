# tiash/laravel-bkash

Complete bKash payment integration for Laravel — Tokenized Checkout, Agreements, B2C Payouts, Webhooks.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-8892BF.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-6%2B-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

## Features

- **Tokenized Checkout** — create, execute, query, search payments (modes 0011 + 0001)
- **Refunds** — refund + refund status (correct `/refund/status` endpoint)
- **Agreements** — create/execute saved-wallet agreements (Phase 2)
- **B2C Payouts** — instant disbursement to beneficiaries (Phase 2)
- **Webhooks** — AWS SNS signature verification, auto-subscription, event dispatch (Phase 2)
- **Callbacks** — signature validation on redirect callbacks
- **Shared token cache** — single grant token per account, auto-refresh before expiry
- **Idempotency** — execute-once guard + webhook deduplication
- **Fail-fast config validation** — missing production creds throw at boot
- **Amount formatting** — prevents float `"2300.0"` → `"2300.00"` rejections

## Installation

```bash
composer require tiash/laravel-bkash
```

```bash
php artisan bkash:install
```

## Configuration

Published to `config/bkash.php`:

```php
'accounts' => [
    'default' => [
        'app_key'    => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'username'   => env('BKASH_USERNAME'),
        'password'   => env('BKASH_PASSWORD'),
    ],
    // 'secondary' => [ ... ]
],
'sandbox'      => env('BKASH_SANDBOX', true),
'callback_url' => env('BKASH_CALLBACK_URL'),
'api_version'  => 'v1.2.0-beta', // one-line upgrade when bKash ships v1.3
```

## Usage

```php
use Tiash\LaravelBkash\Facades\Bkash;

// Create payment
$response = Bkash::payment()->create([
    'mode'                  => '0011',
    'payerReference'        => 'ORDER_123',
    'callbackURL'           => 'https://yourapp.com/bkash/callback',
    'amount'                => 100.00,
    'currency'              => 'BDT',
    'intent'                => 'sale',
    'merchantInvoiceNumber' => 'INV_123',
]);

// Redirect user to $response['bkashURL']

// Callback route (auto-registered) handles execute + signature verification
```

### Refunds

```php
Bkash::refund()->refund($paymentId, $trxId, 50.00, 'Customer request');
Bkash::refund()->status($paymentId, $trxId);
```

### Multi-account

```php
Bkash::payment()->create($data, 'secondary');
Bkash::refund()->refund($pid, $tid, 10, 'reason', 'sku', 'secondary');
```

## Webhooks (Phase 2)

```php
// In EventServiceProvider
protected $listen = [
    \Tiash\LaravelBkash\Events\PaymentCompleted::class => [
        \App\Listeners\FulfillOrder::class,
    ],
];
```

## Testing

```bash
# Configure sandbox credentials in .env
php artisan bkash:test-sandbox
```

## Requirements

- PHP 7.4+
- Laravel 6+
- Guzzle 7+

## License

MIT