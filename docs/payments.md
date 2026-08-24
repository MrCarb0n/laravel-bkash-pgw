# Payments

## Create

```php
use Tiash\LaravelBkash\Facades\Bkash;

$response = Bkash::payment()->create([
    'mode'                  => '0011',   // 0011 = checkout, 0001 = agreement payment
    'payerReference'        => 'ORDER_123',
    'callbackURL'           => route('bkash.callback'),
    'amount'                => 100.00,
    'currency'              => 'BDT',
    'intent'                => 'sale',
    'merchantInvoiceNumber' => 'INV_123',
]);
```

Amounts are formatted to two decimals (`100.00`) before sending.

Response:

```php
[
    'paymentID'  => 'TR0011xxOTmQW1787578438417', // expires after 24h, one execution only
    'bkashURL'   => 'https://sandbox.payment.bkash.com/...',
    'statusCode' => '0000',
]
```

Redirect the user to `bkashURL`.

## Callback

The `/bkash/callback` route is registered automatically. On `status=success` it executes the payment and fires events.

There is no callback signature check: bKash's callback signing scheme is undocumented. The execute API is the verification — it is a server-to-server, single-use call that only completes for a genuinely paid paymentID.

Override the pages by publishing views:

```bash
php artisan vendor:publish --tag=bkash-views
```

For multiple accounts, append `&account=secondary` to your callback URL — the controller uses it to select the right `app_secret` and execute against the right account.

You can also handle execution yourself instead of the built-in route:

```php
$response = Bkash::payment()->execute($paymentId);

if (($response['transactionStatus'] ?? '') === 'Completed') {
    // paid
}
```

`execute()` is guarded by an idempotency lock per paymentID, so double-submits cannot charge twice.

## Query status

```php
$status = Bkash::payment()->query($paymentId);
// $status['transactionStatus'] — Initiated | Completed | Failed | Cancelled
```

## Search by trxID

```php
$txn = Bkash::payment()->search($trxId);
// amount, completedTime, customerMsisdn, transactionStatus, transactionType ...
```

Use this to confirm a transaction when you only have the bKash trxID (e.g. from a webhook).

## Events

| Event | Fired when |
|---|---|
| `PaymentCompleted` | callback/webhook reports `Completed` |
| `PaymentFailed` | callback/webhook reports `Failed`/`Cancelled` |

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Tiash\LaravelBkash\Events\PaymentCompleted::class => [
        \App\Listeners\FulfillOrder::class,
    ],
];
```

```php
class FulfillOrder
{
    public function handle(PaymentCompleted $event): void
    {
        $data = $event->data;

        Order::where('invoice_number', $data['merchantInvoiceNumber'])
            ->update(['status' => 'paid', 'trx_id' => $data['trxID']]);
    }
}
```
