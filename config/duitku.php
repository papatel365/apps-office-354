<?php

use Illuminate\Support\Facades\Request;

return [
    /*
    |--------------------------------------------------------------------------
    | Duitku Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi payment gateway Duitku.
    | Untuk production, gunakan API key yang valid dari Duitku.
    |
    */

    // Base URL - Ganti ke production URL saat deploy
    'base_url' => env('DUITKU_BASE_URL', 'https://api-sandbox.duitku.com'),

    // Merchant Code dari Duitku
    'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),

    // API Key dari Duitku
    'api_key' => env('DUITKU_API_KEY', ''),

    // Production mode
    'production' => env('DUITKU_PRODUCTION', false),

    // Callback URL - harus HTTPS di production
    'callback_url' => env('DUITKU_CALLBACK_URL', '/api/duitku/callback'),

    // Return URL after payment
    'return_url' => env('DUITKU_RETURN_URL', '/marketplace'),

    // Expiry period in hours
    'expiry_period' => env('DUITKU_EXPIRY_PERIOD', 24),
];
