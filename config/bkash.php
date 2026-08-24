<?php

return [

    'sandbox' => env('BKASH_SANDBOX', true),

    'api_version' => 'v1.2.0-beta',

    'base_urls' => [
        'sandbox'    => env('BKASH_SANDBOX_BASE_URL', 'https://tokenized.sandbox.bka.sh'),
        'production' => env('BKASH_PRODUCTION_BASE_URL', 'https://tokenized.pay.bka.sh'),
    ],

    'callback_url' => env('BKASH_CALLBACK_URL'),

    'accounts' => [
        'default' => [
            'app_key'    => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username'   => env('BKASH_USERNAME'),
            'password'   => env('BKASH_PASSWORD'),
        ],
    ],

    'cache' => [
        'store'      => null,
        'ttl_buffer' => 300,
    ],

    'http' => [
        'timeout'         => 30,
        'connect_timeout' => 10,
        'retry'           => 2,
    ],

    'log' => [
        'channel'    => null,
        'log_tokens' => false,
    ],

];