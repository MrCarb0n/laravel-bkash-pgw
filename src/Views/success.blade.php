<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Successful</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; text-align: center; }
        .success { color: #28a745; }
        .trx-id { background: #f8f9fa; padding: 10px; margin: 20px 0; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <h1 class="success">✓ Payment Successful</h1>
    <p>{{ $message }}</p>
    @if($trxId)
        <div class="trx-id">Transaction ID: {{ $trxId }}</div>
    @endif
    <p>Thank you for your payment.</p>
</body>
</html>