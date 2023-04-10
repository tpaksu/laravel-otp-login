<?php

namespace tpaksu\LaravelOTPLogin\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use tpaksu\LaravelOTPLogin\ServiceInterface;

/**
 * Nexmo SMS service handler
 *
 * @namespace tpaksu\LaravelOTPLogin\Services
 */
class Nexmo implements ServiceInterface
{
    /**
     * API key given by nexmo
     *
     * @var string
     */
    private $api_key;

    /**
     * API Secret given by nexmo
     *
     * @var string
     */
    private $api_secret;

    /**
     * The message to be send to the user
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
     * FROM number given by nexmo
     *
     * @var string
     */
    private $from;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->from = config('otp.services.nexmo.from', "");
        $this->api_key = config('otp.services.nexmo.api_key', "");
        $this->api_secret = config('otp.services.nexmo.api_secret', "");
        $this->message = trans('laravel-otp-login::messages.otp_message');
        $this->phone_column = config('otp.user_phone_field');
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
        // extract the phone from the user
        $user_phone = data_get($user, $this->phone_column, false);

        // if the phone isn't set, return false
        if (!$user_phone) {
            return false;
        }

        try {
            // prepare the request url
            $url = $this->buildURL($this->api_key, $this->api_secret, $user_phone, $this->from, $otp, $this->message);
            // prepare the CURL channel
            $response = $this->sendRequest($url);
            // check if response contains the succeeded flag
            return strpos($response, '"status": "0",') !== false;
        } catch (\Exception $e) {
            // return false if any exception occurs
            return false;
        }
    }

    public function buildURL($api_key, $api_secret, $user_phone, $from, $otp, $message)
    {
        return 'https://rest.nexmo.com/sms/json?' . http_build_query([
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'to' => $user_phone,
            'from' => $from,
            'text' => iconv("UTF-8", "ASCII//TRANSLIT", str_replace(":password", $otp, $message))
        ]);
    }

    public function sendRequest($url)
    {
         // prepare the CURL channel
         $ch = curl_init($url);

         // should return the transfer
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

         // execute the request
         return curl_exec($ch);
    }
}
