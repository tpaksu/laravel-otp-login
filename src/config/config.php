<?php
return [
    'otp_service_enabled' => true,
    'otp_default_service' => env("OTP_SERVICE", "biotekno"),
    'services' => [
        'biotekno' => [
            "class" => \tpaksu\LaravelOTPLogin\Services\BioTekno::class,
            "username" => env('OTP_USERNAME', 'null'),
            "password" => env('OTP_PASSWORD', 'null'),
            "user_phone_field" => 'phone',
            "transmission_id" => env('OTP_TRANSMISSION_ID', 'null')
        ]
    ],
    'otp_reference_number_length' => 6,
    'otp_timeout' => 300,
    'otp_digit_length' => 6,
];
