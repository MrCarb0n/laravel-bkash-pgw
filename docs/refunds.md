# Refunds

## Full or partial refund

```php
$refund = Bkash::refund()->refund(
    $paymentId,          // from create response
    $trxId,              // from execute response / webhook
    50.00,               // amount
    'Customer request',  // reason (max 255 chars)
    'SKU_123',           // sku (max 255 chars, optional)
    'secondary'          // account (optional)
);
```

Response:

```php
[
    'originalTrxId'           => 'BFD90JRLST',
    'refundTrxId'             => 'BFD90JRMH9',
    'refundTransactionStatus' => 'Completed',  // anything else = failed
    'refundAmount'            => '50.00',
]
```

bKash allows up to **10 partial refunds** per transaction until the original amount is fully refunded.

If the API times out (30s), call `status()` — do not blindly retry.

## Refund status

```php
$status = Bkash::refund()->status($paymentId, $trxId);
```

Response contains a list of every individual refund:

```php
[
    'originalTrxId'    => 'BFD90JRLST',
    'originalTrxAmount' => '4.59',
    'refundTransactions' => [
        ['refundTrxId' => 'BFD90JRMH9', 'refundTransactionStatus' => 'Completed', 'refundAmount' => '1.00'],
        ['refundTrxId' => 'BFD90JRMK7', 'refundTransactionStatus' => 'Completed', 'refundAmount' => '2.00'],
    ],
]
```

## Common refund errors

| Code | Message |
|---|---|
| 2071 | Refund window expired |
| 2072 | Refund amount not valid |
| 2073 | Invalid SKU |
| 2074 | Transaction cannot be reversed |
| 2077 | Invalid TrxID |
| 2127 | Transaction not yet completed |
| 2023 | Insufficient balance |

All codes are mapped to readable messages on `ApiException`.
