<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ChildShield Climate AI — Third-Party Service Configuration
    |--------------------------------------------------------------------------
    */

    // ── Africa's Talking ─────────────────────────────────────────────────────
    'africastalking' => [
        'username'  => env('AT_USERNAME', 'sandbox'),
        'api_key'   => env('AT_API_KEY'),
        'sender_id' => env('AT_SENDER_ID', 'ChildShield'),
        'ussd_code' => env('AT_USSD_CODE', '*123#'),
    ],

    // ── WhatsApp ─────────────────────────────────────────────────────────────
    'whatsapp' => [
        'driver'      => env('WA_DRIVER', 'baileys'),
        'baileys_url' => env('WA_BAILEYS_URL', 'http://localhost:3000'),
        'waba_url'    => env('WA_WABA_URL'),
        'waba_token'  => env('WA_WABA_TOKEN'),
    ],

    // ── OpenWeather ──────────────────────────────────────────────────────────
    'openweather' => [
        'key' => env('OPENWEATHER_API_KEY'),
    ],

    // ── Tomorrow.io ──────────────────────────────────────────────────────────
    'tomorrow' => [
        'key' => env('TOMORROW_API_KEY'),
    ],

    // ── OpenAI ───────────────────────────────────────────────────────────────
    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'model' => 'gpt-4o-mini',
    ],

];
