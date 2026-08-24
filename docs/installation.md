# Installation

## Requirements

- PHP 7.4+
- Laravel 6+
- Guzzle 7+
- bKash merchant account ([developer.bka.sh](https://developer.bka.sh))

## Install

```bash
composer require mrcarb0n/laravel-bkash-pgw
php artisan bkash:install
```

`bkash:install` publishes:

- `config/bkash.php`
- `routes/bkash.php` (callback + webhook routes)
- `resources/views/vendor/bkash/` (success/failed pages)

You can publish each individually:

```bash
php artisan vendor:publish --tag=bkash-config
php artisan vendor:publish --tag=bkash-routes
php artisan vendor:publish --tag=bkash-views
```

## Configuration

Add to `.env`:

```env
BKASH_SANDBOX=true
BKASH_APP_KEY=your_sandbox_app_key
BKASH_APP_SECRET=your_sandbox_app_secret
BKASH_USERNAME=your_sandbox_username
BKASH_PASSWORD=your_sandbox_password
BKASH_CALLBACK_URL=https://yourdomain.com/bkash/callback
```

Sandbox credentials are available at [developer.bka.sh](https://developer.bka.sh) under Tokenized Checkout → Sandbox.

The published `config/bkash.php` reads these via `env()`:

```php
'accounts' => [
    'default' => [
        'app_key'    => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'username'   => env('BKASH_USERNAME'),
        'password'   => env('BKASH_PASSWORD'),
    ],
],
'sandbox'      => env('BKASH_SANDBOX', true),
'callback_url' => env('BKASH_CALLBACK_URL'),
'api_version'  => 'v1.2.0-beta',
```

### Multiple accounts

Add more entries under `accounts` and pass the account name to any API call:

```php
'accounts' => [
    'default'   => [...],
    'secondary' => [
        'app_key'    => env('BKASH_SECONDARY_APP_KEY'),
        'app_secret' => env('BKASH_SECONDARY_APP_SECRET'),
        'username'   => env('BKASH_SECONDARY_USERNAME'),
        'password'   => env('BKASH_SECONDARY_PASSWORD'),
    ],
],
```

```php
Bkash::payment()->create($data, 'secondary');
```

### Cache store

Tokens are cached per account with TTL = `expires_in − ttl_buffer` (default buffer: 300s). To use a specific cache store (e.g. redis):

```php
'cache' => [
    'store'      => 'redis',
    'ttl_buffer' => 300,
],
```

## Verify setup

```bash
php artisan bkash:test-sandbox
```

Runs token grant → create payment → query status against sandbox and prints a payment URL you can complete manually (sandbox wallets: OTP `123456`, PIN `12121`).
