<?php
return [
    'otp_service_enabled' => true,
    'otp_message' => env("OTP_MESSAGE", "Your one-time password is"),
    'opt_reference_number_length' => 8,
    'opt_digit_length' => 4,
    'encode_password' => false,
];
