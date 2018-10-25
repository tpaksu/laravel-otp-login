<?php

namespace tpaksu\LaravelOTPLogin\Services;

use App\User;
use tpaksu\LaravelOTPLogin\ServiceInterface;

class BioTekno implements ServiceInterface
{
    private $username;
    private $password;
    private $message;

    public function __construct()
    {
        $this->username = config('otp.services.biotekno.username', "");
        $this->password = config('otp.services.biotekno.password', "");
        $this->message = config('otp.otp_message', "");
        $this->transmissionID = config('otp.services.biotekno.transmission_id', "");
    }
    public function sendOneTimePassword(User $user, $otp)
    {
        if (empty($user->phone)) return false;
        try {
            $url = 'http://www.biotekno.biz:8080/SMS-Web/HttpSmsSend?' .
                http_build_query([
                'Username' => $this->username,
                'Password' => $this->password,
                'Msisdns' => $user->phone,
                'TransmissionID' => $this->transmissionID,
                'Messages' => str_replace(":password", $otp, $this->message)
            ], null, '&', PHP_QUERY_RFC3986);
            $results = file_get_contents($url);
            \Debugbar::info([$url, $results]);
            return strpos($results, "Status=0") !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
