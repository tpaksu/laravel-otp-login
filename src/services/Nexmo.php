<?php

namespace tpaksu\LaravelOTPLogin\Services;

use App\User;
use tpaksu\LaravelOTPLogin\ServiceInterface;

class Nexmo implements ServiceInterface
{
    private $api_key;
    private $api_secret;
    private $message;
    private $phone_column;

    public function __construct()
    {
        $this->from = config('otp.services.nexmo.from', "");
        $this->api_key = config('otp.services.nexmo.api_key', "");
        $this->api_secret = config('otp.services.nexmo.api_secret', "");
        $this->message =  trans('laravel-otp-login::messages.otp_message');
        $this->phone_column = config('otp.services.user_phone_field');
    }
    public function sendOneTimePassword(User $user, $otp)
    {
        // phone numbers need to be starting without a leading zero in this service
        $user_phone = data_get($user, $this->phone_column, false);
        if (!$user_phone) return false;
        try {

            $url = 'https://rest.nexmo.com/sms/json?' . http_build_query([
                'api_key' => $this->api_key,
                'api_secret' => $this->api_secret,
                'to' => $user_phone,
                'from' => $this->from,
                'text' => urlencode(iconv("UTF-8", "ASCII//TRANSLIT", str_replace(":password", $otp, $this->message)))
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            return strpos($response, "\"status\": \"0\",") !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
