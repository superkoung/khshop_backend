<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Use SIT (test) environment
    |--------------------------------------------------------------------------
    |
    | When true, the SDK will use https://sit-api-bakong.nbc.gov.kh
    | When false, the SDK will use https://api-bakong.nbc.gov.kh
    |
    */
    'is_test' => env('BAKONG_IS_TEST', true),

    /*
    |--------------------------------------------------------------------------
    | Bakong API Token
    |--------------------------------------------------------------------------
    |
    | Required for: check_transaction_by_md5, generate_deeplink_by_qr
    | Not required for: local KHQR generation (done by SDK)
    |
    */
    'token' => env('BAKONG_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Bakong Account ID
    |--------------------------------------------------------------------------
    |
    | The merchant's Bakong account ID (exactly one "@", e.g. name@bank)
    | Required for: KHQR generation
    |
    */
    'account_id' => env('BAKONG_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Merchant Name displayed on KHQR
    |--------------------------------------------------------------------------
    */
    'merchant_name' => env('BAKONG_MERCHANT_NAME', 'KhShop'),

    /*
    |--------------------------------------------------------------------------
    | Merchant City
    |--------------------------------------------------------------------------
    */
    'merchant_city' => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),

    /*
    |--------------------------------------------------------------------------
    | Acquiring Bank (optional — Merchant-type KHQR only)
    |--------------------------------------------------------------------------
    |
    | Required only when generating Merchant-type KHQR (used together with
    | BAKONG_MERCHANT_ID). When either is empty, the app falls back to
    | Individual-type KHQR which only needs BAKONG_ACCOUNT_ID.
    | Not required for transaction checking.
    |
    */
    'acquiring_bank' => env('BAKONG_ACQUIRING_BANK'),

    /*
    |--------------------------------------------------------------------------
    | Merchant ID (optional — Merchant-type KHQR only)
    |--------------------------------------------------------------------------
    |
    | Required only when generating Merchant-type KHQR (used together with
    | BAKONG_ACQUIRING_BANK). When either is empty, the app falls back to
    | Individual-type KHQR which only needs BAKONG_ACCOUNT_ID.
    | Not required for transaction checking.
    |
    */
    'merchant_id' => env('BAKONG_MERCHANT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Currency (USD = 840, KHR = 116 for KHQR)
    |--------------------------------------------------------------------------
    */
    'currency' => (int) env('BAKONG_CURRENCY', '840'),

    /*
    |--------------------------------------------------------------------------
    | Payment timeout in seconds (default 10 minutes)
    |--------------------------------------------------------------------------
    */
    'payment_timeout' => env('BAKONG_PAYMENT_TIMEOUT', 600),

    /*
    |--------------------------------------------------------------------------
    | Polling interval in seconds for checking payment status
    |--------------------------------------------------------------------------
    */
    'polling_interval' => env('BAKONG_POLLING_INTERVAL', 8),

];
