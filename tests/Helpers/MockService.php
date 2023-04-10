<?php

namespace tpaksu\LaravelOTPLogin\Tests\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;
use tpaksu\LaravelOTPLogin\ServiceInterface;

/**
 * Mock SMS service handler
 *
 * @namespace tpaksu\LaravelOTPLogin\Tests\Helpers
 */
class MockService implements ServiceInterface
{
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
        global $mockSMSServiceReturn;
        return $mockSMSServiceReturn ?? true;
    }
}
