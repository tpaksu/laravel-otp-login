<?php

namespace tpaksu\LaravelOTPLogin\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use tpaksu\LaravelOTPLogin\ServiceInterface;

/**
 * BioTekno SMS service handler
 *
 * @namespace tpaksu\LaravelOTPLogin\Services
 */
class BioTekno implements ServiceInterface
{
    /**
     * Username for the API
     *
     * @var string
     */
    private $username;

    /**
     * Password for the API
     *
     * @var string
     */
    private $password;

    /**
     * The message to send
     *
     * @var string
     */
    private $message;

    /**
     * The User model's phone field name to be used for sending the SMS
     *
     * @var string
     */
    private $phone_column;

    /**
     * Transmission ID used by the SMS service, the name shown in the delivered message
     *
     * @var string
     */
    private $transmission_id;

    public function __construct()
    {
        $this->username = config('otp.services.biotekno.username', "");
        $this->password = config('otp.services.biotekno.password', "");
        $this->message = trans('laravel-otp-login::messages.otp_message');
        $this->phone_column = config('otp.user_phone_field');
        $this->transmission_id = config('otp.services.biotekno.transmission_id', "");
    }

    /**
     * Sends the generated password to the user and returns if it's successful
     *
     * @param Authenticatable $user
     * @param string $otp
     * @param string $ref
     * @return boolean
     */
    public function sendOneTimePassword(Authenticatable $user, $otp, $ref)
    {
        // phone numbers need to be starting without a leading zero in this service
        // extract the phone number from the user
        $user_phone = data_get($user, $this->phone_column, false);

        // if phone number doesn't exist, return failed
        if (!$user_phone) {
            return false;
        }

        try {
            // prepare the request URL
            $url = $this->buildURL(
                $this->username,
                $this->password,
                $user_phone,
                $this->transmission_id,
                $otp,
                $this->message
            );

            // GET the response
            $results = $this->sendRequest($url);

            // check the result contains the succeeded flag
            return strpos($results, "Status=0") !== false;
        } catch (\Throwable $e) {
            // return false if any exception occurs
            return false;
        }
    }

    public function sendRequest($url)
    {
        return file_get_contents($url);
    }

    public function buildURL($username, $password, $phone, $transmission_id, $otp, $message)
    {
        return 'http://www.biotekno.biz:8080/SMS-Web/HttpSmsSend?' .
            'Username=' . $username .
            '&Password=' . $password .
            '&Msisdns=' . $phone .
            '&TransmissionID=' . $transmission_id .
            '&Messages=' . urlencode(iconv(
                "UTF-8",
                "ASCII//TRANSLIT",
                str_replace(":password", $otp, $message)
            ));
    }
}
