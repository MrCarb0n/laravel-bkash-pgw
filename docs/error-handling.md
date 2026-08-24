# Error handling

## Exceptions

| Exception | When |
|---|---|
| `ApiException` | bKash returned an error payload or HTTP 4xx |
| `NetworkException` | HTTP 5xx, timeout, empty body, non-JSON body |
| `SignatureException` | invalid callback/webhook signature |
| `BkashException` | base class for all of the above |

```php
use Tiash\LaravelBkash\Exceptions\ApiException;
use Tiash\LaravelBkash\Exceptions\NetworkException;
use Tiash\LaravelBkash\Exceptions\SignatureException;

try {
    $response = Bkash::payment()->create([...]);
} catch (ApiException $e) {
    $e->getErrorCode();    // '2023'
    $e->getMessage();      // 'Insufficient Balance'
    $e->getRawResponse();  // full response array
} catch (NetworkException $e) {
    report($e);            // transient — safe to retry for idempotent calls
} catch (SignatureException $e) {
    // do not retry — someone sent a bad request
}
```

`ApiException` also exposes the code via `$e->getCode()` when it is numeric.

## Error code map

150+ bKash codes (2001–2150) are mapped to readable messages. Examples:

| Code | Message |
|---|---|
| 2001 | Invalid App Key |
| 2002 | Invalid Payment ID |
| 2023 | Insufficient Balance |
| 2033 | Transaction not found |
| 2050 | Agreement already exists between payer and merchant |
| 2064 | Customer limit exceeded |
| 2071 | Refund window expired |
| 2086 | Invalid payout ID |

Unknown codes fall back to bKash's original `errorMessage` / `statusMessage`.

Full map: `src/Exceptions/ErrorCodeMap.php`.

## Retries

The HTTP layer retries automatically (config: `bkash.http.retry`, default 2) on:

- GET requests
- Idempotent POST endpoints: execute, payment status, search, agreement execute/status

Non-idempotent POSTs (create, refund, payout) are never retried to avoid double charges.

## Timeouts

Defaults from `config/bkash.php`:

```php
'http' => [
    'timeout'         => 30,
    'connect_timeout' => 10,
    'retry'           => 2,
],
```

bKash recommends a 30-second timeout. If a refund times out, check its status instead of re-submitting.
