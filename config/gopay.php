<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GoPAY Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the GoPAY API.
    |
    */
    'base_url' => env('GOPAY_BASE_URL', 'https://gopay.gooomart.com'),

    /*
    |--------------------------------------------------------------------------
    | GoPAY Payment API Credentials
    |--------------------------------------------------------------------------
    |
    | Keys required to initialize standard payments.
    |
    */
    'api_key' => env('GOPAY_API_KEY'),
    'secret_key' => env('GOPAY_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | GoPAY Payout API Credentials
    |--------------------------------------------------------------------------
    |
    | Keys required for the Payout API.
    |
    */
    'payout_api_key' => env('GOPAY_PAYOUT_API_KEY'),
];
