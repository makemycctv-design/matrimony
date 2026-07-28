<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "log" (safe default, writes to log), and provider drivers you
    | wire up later (e.g. "twilio", "gupshup", "msg91"). On shared hosting
    | without a provider configured, keep this as "log".
    |
    */

    'default' => env('SMS_DRIVER', 'log'),

    'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),

    'from' => env('SMS_FROM', 'MATRMY'),

    'providers' => [
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        'gupshup' => [
            'api_key' => env('GUPSHUP_API_KEY'),
            'source' => env('GUPSHUP_SOURCE'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        // Throttle: max OTP sends per destination within the window.
        'resend_window_seconds' => 60,
    ],

];
