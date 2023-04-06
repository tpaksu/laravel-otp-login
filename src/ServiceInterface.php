<?php
namespace tpaksu\LaravelOTPLogin;

use Illuminate\Auth\Authenticatable;

interface ServiceInterface
{
    /**
     * Sends the OTP to the user with optionally using the reference number
     *
     * @param User $user  : The user who requested the OTP
     * @param string $otp : The One-Time-Password
     * @param string $ref : Reference Number to compare with
     * @return void
     */
    public function sendOneTimePassword(Authenticatable $user, $otp, $ref);
}
