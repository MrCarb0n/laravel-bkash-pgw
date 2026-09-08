# Payouts

Endpoints (per [bKash docs](https://developer.bka.sh/docs/b2b-payout-1)):

- B2C: `POST {checkout-host}/{version}/checkout/payment/b2cPayment`
- B2B: `POST {host}/{version}/tokenized/payout/{initiate,b2b,query}`

The checkout host is configured separately (`checkout_urls`, env `BKASH_CHECKOUT_*_BASE_URL`) because B2C belongs to the Checkout API family, not Tokenized.

## B2C — send money to a wallet

```php
Bkash::payout()->disburse(
    '01700000000',   // receiver MSISDN
    500.00,          // amount
    'INV-12345',     // merchantInvoiceNumber
    'BDT',           // currency (optional)
    'secondary'      // account (optional)
);

// $result['trxID'], $result['transactionStatus'], $result['b2cFee']
```

Requires the B2C product enabled on your merchant account.

## B2B — pay another bKash merchant

The B2B flow has two steps: initiate (returns a `payoutID`), then disburse.

### One call

```php
$result = Bkash::payout()->disburseB2BWorkflow(
    '01700000001',  // receiver merchant MSISDN
    1000.00,
    'INV-12345',
);
```

### Step by step

```php
$initiate = Bkash::payout()->initiateB2B('B2B', 'REF-001');
$payoutId = $initiate['payoutID'];

$disburse = Bkash::payout()->disburseB2B($payoutId, '01700000001', 1000.00, 'INV-12345');

// check later
$status = Bkash::payout()->queryB2B($payoutId);
```

Notes from bKash docs:

- amount must be ≥ 1.00
- `merchantInvoiceNumber`: max 256 chars, no spaces or special characters except `_` and `-`
- On certain error codes you must call `queryB2B()` before retrying — the response tells you if a payout already went through

## Refunds

See [Refunds](refunds.md).
