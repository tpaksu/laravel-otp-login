<?php

namespace tpaksu\LaravelOTPLogin\Services;

use App\User;
use tpaksu\LaravelOTPLogin\ServiceInterface;

/**
 * Twilio SMS service handler
 *
 * @namespace tpaksu\LaravelOTPLogin\Services
 */
class Twilio implements ServiceInterface
{
    /**
     * API Account SID given from twilio
     *
     * @var string
     */
    private $api_account_sid;

    /**
     * API Auth token given from twilio
     *
     * @var string
     */
    private $api_auth_token;

    /**
     * The message to be send to the user
     *
     * @var [type]
     */
    private $message;

    /**
     * The User model's phone field name to be used for sending the SMS
     *
     * @var string
     */
    private $phone_column;

    /**
     * FROM number given by twilio
     *
     * @var string
     */
    private $from;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->from = config('otp.services.twilio.from', "");
        $this->api_account_sid = config('otp.services.twilio.account_sid', "");
        $this->api_auth_token = config('otp.services.twilio.auth_token', "");
        $this->message = trans('laravel-otp-login::messages.otp_message');
        $this->phone_column = config('otp.user_phone_field');
    }

    /**
     * Sends the generated password to the user and returns if it's successful
     *
     * @param App\User $user
     * @param string $otp
     * @param string $ref
     * @return boolean
     */
    public function sendOneTimePassword(User $user, $otp, $ref)
    {
        // extract the phone from the user
        $user_phone = data_get($user, $this->phone_column, false);

        // if the phone isn't set, return false
        if (!$user_phone) return false;

        try {

            // prepare the request url
            $url = "https://api.twilio.com/2010-04-01/Accounts/" . $this->api_account_sid . "/Messages.json";

            // prepare the CURL channel
            $ch = curl_init($url);

            // set the request type to POST
            curl_setopt($ch, CURLOPT_POST, 1);

            // prepare the body data
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                "Body" => iconv("UTF-8", "ASCII//TRANSLIT", str_replace(":password", $otp, $this->message)),
                "From" => $this->from,
                "To" => $user_phone
            ]);

            // add the authentication info
            curl_setopt($ch, CURLOPT_USERPWD, $this->api_account_sid . ":" . $this->api_auth_token);

            // should return the response
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // execute the request and get the response
            $response = curl_exec($ch);

            // check if the response contains the success flag
            return strpos($response, "\"status\": \"queued\",") !== false;

        } catch (\Exception $e) {

            // return false if any exception occurs
            return false;
        }
    }
}
