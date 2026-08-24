# Going live

## Checklist

1. Set `BKASH_SANDBOX=false` in `.env`
2. Replace credentials with production values (app key/secret, username, password)
3. Whitelist your server's static IP with bKash — production API calls fail without it
4. Use HTTPS for `BKASH_CALLBACK_URL` and the webhook URL (no localhost)
5. Share `https://yourdomain.com/bkash/webhook` with bKash support to provision notifications
6. Run one real transaction end-to-end and confirm the `PaymentCompleted` listener fires

The base URL switches automatically:

- Sandbox: `https://tokenized.sandbox.bka.sh/{api_version}/tokenized`
- Production: `https://tokenized.pay.bka.sh/{api_version}/tokenized`

When bKash ships a new API version, change `api_version` in `config/bkash.php`.

## Production validation

In production the package validates config at boot and throws if any account is missing `app_key`, `app_secret`, `username`, or `password`, or if `callback_url` is empty. Nothing fails silently.

## Operational notes

- **Tokens** are cached per account; refresh happens before expiry (max 2 refreshes/hour per bKash limits). A lock prevents concurrent workers from requesting duplicate grants.
- **paymentID** expires after 24 hours and can only be executed once.
- **Webhook dedup** keys live in cache for 24h; use a persistent store (redis/memcached) in production so dedup survives deploys.
- Monitor for `ApiException` codes `2100–2103` (system maintenance/unavailable) — treat as transient.

## Upgrading from sandbox

No code changes needed — only `.env` values differ between sandbox and production.
