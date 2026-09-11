# Roadmap

Planned work after v1.0.0, ordered by adopter value. Every item ships with
tests + docs + CHANGELOG entry. Minor versions stay backward-compatible;
breaking changes wait for a major.

## Next (v1.1.0) — multi-merchant and operational safety

### 1. Named merchant accounts
Merchants commonly run 2+ bKash accounts (brands, sandbox/prod split).
Support named accounts instead of one `default`:

```php
'accounts' => [
    'default' => [...],
    'brand_b' => [...], // BKASH_BRANDB_APP_KEY, ...
],
```

```php
Bkash::account('brand_b')->payment()->create([...]);
```

- Token cache keyed per account; missing account fails fast with a named exception.
- Acceptance: two accounts with different keys grant and cache tokens
  independently; `account()` with unknown name throws.

### 2. `minimum-stability: stable`
v1.0.0 is stable; stop inviting dev dependencies downstream.

### 3. Settlement reconciliation tooling
Every production merchant reconciles gateway vs ledger daily. Ship a
`bkash:reconcile --date=YYYY-MM-DD` command plus a `docs/reconciliation.md`
guide: pull the day's transactions (search/query), compare against the
app's order table via a developer-supplied query callback, and report
matched / gateway-only / ledger-only rows with amounts. Read-only against
money — it reports, never fulfills or refunds.
- Acceptance: seeded mismatch fixture yields exactly the three buckets
  with correct totals; zero gateway writes during a run.

### 4. Callback safety guide
bKash callbacks are GET redirects — replayable by refresh. Document the
required app-side pattern (unique `paymentID` constraint, verify-before-fulfill,
dedupe on `trxID`) with a copy-paste example in `docs/payments.md`.

## Then (v1.2.0) — money-grade assurance

### 5. HTTP-level feature tests
Unit coverage exists; add Guzzle-mock feature tests per flow: create →
execute → query fallback, refund up to the 10-per-transaction limit,
agreement execute, callback handler success/fail/cancel paths.

### 6. PHPStan max
Raise from level 5 to max and clear the findings. Money paths deserve the
strictest static analysis we can get for free.

### 7. CI matrix
No CI yet. Add PHP 8.1–8.4 × Laravel 10–12 running `composer test` +
`composer analyse` + cs-fixer dry-run on every push.

## Later — live verification and future-proofing

### 8. Live verification tracker
Payout fund movement and SNS delivery are path-verified only (need
bKash-side enablement — see `docs/payouts.md`, `docs/webhooks.md`).
Track publicly until fired live, then note versions in the CHANGELOG.

### 9. Overridable `api_version`
Currently pinned to `v1.2.0-beta`. Make it env-overridable (per account
once §1 lands) so a bKash version bump doesn't require a release.

## Non-goals
- Checkout (non-tokenized) API — superseded by bKash.
- Storing merchant secrets anywhere but env/config.
- Framework support outside Laravel 6+ / PHP 7.4+.
