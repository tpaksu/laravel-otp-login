<?php
namespace tpaksu\LaravelOTPLogin;

class ServiceFactory
{
    public function getService($serviceName)
    {
        if ($serviceName == "biotekno") {
            return new BioTekno();
        } else {
            return null;
        }
    }
}
