<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Failed</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; text-align: center; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <h1 class="error">✗ Payment Failed</h1>
    <p>{{ $message }}</p>
    <p>Please try again or contact support if the issue persists.</p>
</body>
</html>