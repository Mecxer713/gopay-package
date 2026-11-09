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

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    |
    | Number of retries (exponential backoff + jitter) applied to idempotent
    | GET requests on network errors or HTTP 408/429/5xx. POST and DELETE
    | requests are never retried to avoid double debits.
    |
    */
    'max_retries' => (int) env('GOPAY_MAX_RETRIES', 2),
    'retry_base_delay_ms' => (int) env('GOPAY_RETRY_BASE_DELAY_MS', 300),
];
