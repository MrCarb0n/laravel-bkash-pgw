# Agreements

An agreement lets a customer authorize recurring payments to your merchant account with only their wallet PIN — no OTP on later charges.

## Flow

1. **Create** → redirect customer to `bkashURL`
2. Customer enters wallet number + OTP
3. bKash redirects back to your callback with `paymentID`
4. **Execute** the paymentID → you receive an `agreementID`
5. Store the `agreementID` — use it for future payments (mode `0001`)

## Create

```php
$agreement = Bkash::agreement()->create([
    'payerReference' => '01712345678',   // your customer identifier
    'callbackURL'    => route('bkash.callback'),
], 'secondary');
```

Redirect to `$agreement['bkashURL']`.

## Execute

After the callback (`status=success`):

```php
$executed = Bkash::agreement()->execute($paymentId);

// $executed['agreementID']      — store this
// $executed['agreementStatus']  — Completed
// $executed['customerMsisdn']
```

## Status

```php
$status = Bkash::agreement()->status($agreementId);
// $status['agreementStatus'] — Active | Cancelled | ...
```

## Charge against an agreement

Once the agreement is active, create payments without redirecting through full checkout:

```php
$payment = Bkash::payment()->create([
    'mode'                  => '0001',
    'agreementID'           => $agreementId,
    'payerReference'        => 'ORDER_456',
    'callbackURL'           => route('bkash.callback'),
    'amount'                => 250.00,
    'currency'              => 'BDT',
    'intent'                => 'sale',
    'merchantInvoiceNumber' => 'INV_789',
]);
```

The callback/execute flow is identical to regular payments.

## Error codes specific to agreements

| Code | Message |
|---|---|
| 2050 | Agreement already exists between payer and merchant |
| 2051 | Invalid Agreement ID |
| 2052 | Agreement in incomplete state |
| 2053 | Agreement already cancelled |
| 2036 | Mandate not in Active state |
