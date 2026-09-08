# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0-beta] - 2026-09-08

First release.

### Added

- Token grant and refresh with shared per-account cache (TTL = `expires_in` − buffer, lock against duplicate grants; refresh sends full credentials per official docs)
- Payments: create, execute (idempotent), query status, search by trxID; forwards `merchantAssociationInfo` when provided
- Refunds: full or partial (up to 10 per transaction) with refund status lookup, via documented v2 endpoints
- Agreements: create (mode 0000), execute via shared `/checkout/execute`, status
- Payouts: B2C disbursement; B2B initiate, disburse, query, and one-call workflow
- Configurable API base URLs (`base_urls` + `BKASH_*_BASE_URL` env overrides)
- SNS webhook endpoint: RSA-SHA1 signature verification, certificate host allowlist, MessageId deduplication, automatic subscription confirmation
- Redirect callback endpoint: executes the payment (single-use execute call is the verification), multi-account support via `account` query parameter
- Events: `PaymentCompleted`, `PaymentFailed`, `RefundCompleted`, `WebhookReceived`
- Error code map: 150+ bKash error codes translated to readable messages
- Webhook transaction type constants with helpers (`isPayment`, `isM2M`, `isB2B`)
- Amount formatter producing two-decimal strings
- HTTP retry on GET and idempotent POST endpoints
- 4xx response handling mapped to `ApiException` (numeric code preserved)
- Atomic idempotency via `cache::add()`
- Artisan commands: `bkash:install`, `bkash:test-sandbox`
- `composer test` and `composer analyse` scripts
- Per-topic guides under `docs/`; CSRF-exclusion note for the webhook route

### Compatibility

- PHP 7.4–8.3
- Laravel 6–12

[1.0.0-beta]: https://github.com/MrCarb0n/laravel-bkash-pgw/releases/tag/v1.0.0-beta
