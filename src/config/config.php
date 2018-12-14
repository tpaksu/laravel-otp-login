<?php
return [
    'otp_service_enabled' => true,
    'otp_default_service' => env("OTP_SERVICE", "nexmo"),
    'services' => [
        'biotekno' => [
            "class" => \tpaksu\LaravelOTPLogin\Services\BioTekno::class,
            "username" => env('OTP_USERNAME', null),
            "password" => env('OTP_PASSWORD', null),
            "transmission_id" => env('OTP_TRANSMISSION_ID', null)
        ],
        'nexmo' => [
            'class' => \tpaksu\LaravelOTPLogin\Services\Nexmo::class,
            'api_key' => env("OTP_API_KEY", null),
            'api_secret' => env('OTP_API_SECRET', null),
            'from' => env('OTP_FROM', null)
        ],
        'twilio' => [
            'class' => \tpaksu\LaravelOTPLogin\Services\Twilio::class,
            'account_sid' => env("OTP_ACCOUNT_SID", null),
            'auth_token' => env("OTP_AUTH_TOKEN", null),
            'from' => env("OTP_FROM", null)
        ]
    ],
    'user_phone_field' => 'phone',
    'otp_reference_number_length' => 6,
    'otp_timeout' => 7890000,
    'otp_digit_length' => 6,
    'encode_password' => false
];
