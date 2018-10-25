<?php
namespace tpaksu\LaravelOTPLogin;

use tpaksu\LaravelOTPLogin\Services;

class ServiceFactory
{
    public function getService($serviceName)
    {
        if ($serviceName == "biotekno") {
            return new Services\BioTekno();
        } else {
            return null;
        }
    }
}
