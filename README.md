# laravel-bkash-pgw

bKash PGW (Tokenized Checkout) integration for Laravel — payments, refunds, agreements, payouts, webhooks.

[![Packagist Version](https://img.shields.io/packagist/v/mrcarb0n/laravel-bkash-pgw.svg)](https://packagist.org/packages/mrcarb0n/laravel-bkash-pgw)
[![Packagist Downloads](https://img.shields.io/packagist/dt/mrcarb0n/laravel-bkash-pgw.svg)](https://packagist.org/packages/mrcarb0n/laravel-bkash-pgw)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-8892BF.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-6%2B-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg)](https://phpstan.org)

## Features

- **Payments** — create, execute (idempotent), query, search
- **Refunds** — full or partial, up to 10 per transaction, with status lookup
- **Agreements** — saved-wallet mandates for PIN-only recurring charges
- **Payouts** — B2C disbursement and B2B merchant transfers (initiate/disburse/query)
- **Webhooks** — AWS SNS signature verification, auto-subscription, event dispatch
- **Callbacks** — redirect handler executes the payment and fires events
- **Token cache** — one grant per account, auto-refresh, lock against duplicate grants
- **Error codes** — 150+ bKash codes mapped to readable messages

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

Copy the variables from [.env.example](.env.example) into your app's `.env` and fill in your sandbox credentials — see [docs/installation.md](docs/installation.md).

Verify connectivity:

```bash
php artisan bkash:test-sandbox
```

## Quick start

```php
use Tiash\LaravelBkash\Facades\Bkash;

$response = Bkash::payment()->create([
    'mode'                  => '0011',
    'payerReference'        => 'ORDER_123',
    'callbackURL'           => route('bkash.callback'),
    'amount'                => 100.00,
    'currency'              => 'BDT',
    'intent'                => 'sale',
    'merchantInvoiceNumber' => 'INV_123',
]);

return redirect($response['bkashURL']);
```

Fulfil orders by listening for `PaymentCompleted` — full flow in [docs/payments.md](docs/payments.md).

## Documentation

| Doc | Contents |
|---|---|
| [Installation](docs/installation.md) | install, config, multi-account, cache store |
| [Payments](docs/payments.md) | create/execute/query/search, callback flow, events |
| [Refunds](docs/refunds.md) | full/partial refunds, status, error codes |
| [Agreements](docs/agreements.md) | saved-wallet mandate lifecycle |
| [Payouts](docs/payouts.md) | B2C disbursement, B2B initiate/disburse/query |
| [Webhooks](docs/webhooks.md) | SNS verification, events, transaction types |
| [Error handling](docs/error-handling.md) | exceptions, error code map, retry behavior |
| [Going live](docs/going-live.md) | production checklist |

## Testing

```bash
composer test                                    # 42 tests
vendor/bin/phpstan analyse --no-progress         # static analysis
php artisan bkash:test-sandbox                   # live sandbox smoke test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Security

If you find a vulnerability, open a private security advisory instead of a public issue.

## License

MIT — see [LICENSE](LICENSE).