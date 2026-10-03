<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paddle Environment
    |--------------------------------------------------------------------------
    |
    | Supported values: "sandbox", "production"
    |
    */
    'environment' => env('PADDLE_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Paddle Credentials
    |--------------------------------------------------------------------------
    |
    | API key is server-side only.
    | Client token may be exposed to Paddle.js on the frontend.
    | Webhook secret is used to verify incoming Paddle notifications.
    |
    */
    'api_key' => env('PADDLE_API_KEY'),

    'client_token' => env('PADDLE_CLIENT_TOKEN'),

    'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Paddle Tax Categories
    |--------------------------------------------------------------------------
    |
    | These categories must also be enabled for the Paddle account.
    |
    */
    'tax_categories' => [
        'product' => env('PADDLE_PRODUCT_TAX_CATEGORY', 'standard'),
        'customization' => env(
            'PADDLE_CUSTOMIZATION_TAX_CATEGORY',
            'software-programming-services'
        ),
    ],
];
