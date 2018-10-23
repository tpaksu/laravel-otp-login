<?php
namespace tpaksu\LaravelOTPLogin;

use App\User;

interface ServiceInterface
{
    public function sendOneTimePassword(User $user, $otp);
}
