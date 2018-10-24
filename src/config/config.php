<?php
return [
    'otp_service_enabled' => true,
    'otp_default_service' => env("OTP_SERVICE", "biotekno"),
    'otp_message' => env("OTP_MESSAGE", "Your one-time password is :password"),
    'services' => [
        'biotekno' => [
            "type" => "url",
            "username" => env('OTP_USERNAME', 'null'),
            "password" => env('OTP_PASSWORD', 'null'),
            "transID" => env('OTP_TRANSMISSION_ID', 'null')
        ]
    ],
    'otp_reference_number_length' => 6,
    'otp_timeout' => 300,
    'otp_digit_length' => 6,
    'encode_password' => false,
];
