<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform defaults
    |--------------------------------------------------------------------------
    */

    'currency' => env('PLATFORM_CURRENCY', 'IDR'),

    'subscription' => [
        'duration_days' => (int) env('PLATFORM_SUBSCRIPTION_DURATION_DAYS', 30),
        'grace_days' => (int) env('PLATFORM_GRACE_PERIOD_DAYS', 0),
        'reminder_days' => [7, 3, 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment gateway
    |--------------------------------------------------------------------------
    |
    | The active gateway is resolved through the PaymentGatewayManager. Secrets
    | are only read from the environment. When MIDTRANS_SNAP_BASE_URL is not
    | provided the correct sandbox/production endpoint is chosen automatically.
    |
    */

    'payment' => [
        'default' => env('PAYMENT_GATEWAY', 'manual'),
        'gateways' => [
            'manual' => [
                'driver' => 'manual',
            ],
            'fake' => [
                'driver' => 'fake',
            ],
            'midtrans' => [
                'driver' => 'midtrans',
                'server_key' => env('MIDTRANS_SERVER_KEY', ''),
                'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
                'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
                'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
                'snap_base_url' => env('MIDTRANS_SNAP_BASE_URL'),
                'sanitize' => (bool) env('MIDTRANS_SANITIZE', false),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue behaviour for notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'queue' => env('QUEUE_CONNECTION', 'database') !== 'sync',
    ],

];
