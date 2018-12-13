<?php

namespace tpaksu\LaravelOTPLogin\Services;

use App\User;
use tpaksu\LaravelOTPLogin\ServiceInterface;

class BioTekno implements ServiceInterface
{
    private $username;
    private $password;
    private $message;
    private $phone_column;

    public function __construct()
    {
        $this->username = config('otp.services.biotekno.username', "");
        $this->password = config('otp.services.biotekno.password', "");
        $this->message =  trans('laravel-otp-login::messages.otp_message');
        $this->phone_column = config('otp.services.biotekno.user_phone_field');
        $this->transmissionID = config('otp.services.biotekno.transmission_id', "");
    }
    public function sendOneTimePassword(User $user, $otp)
    {
        // phone numbers need to be starting without a leading zero in this service
        $user_phone = data_get($user, $this->phone_column, false);
        if (!$user_phone) return false;
        try {
            $url = 'http://www.biotekno.biz:8080/SMS-Web/HttpSmsSend?' .
                'Username=' . $this->username .
                '&Password=' . $this->password .
                '&Msisdns=' . $user_phone .
                '&TransmissionID=' . $this->transmissionID .
                '&Messages=' . urlencode(iconv("UTF-8", "ASCII//TRANSLIT", str_replace(":password", $otp, $this->message)));
            $results = file_get_contents($url);
            return strpos($results, "Status=0") !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
