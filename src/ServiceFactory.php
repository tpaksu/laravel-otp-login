<?php
namespace tpaksu\LaravelOTPLogin;

use tpaksu\LaravelOTPLogin\Services;

class ServiceFactory
{
    public function getService($serviceName)
    {
        $services = config("otp.services", []);
        if (isset($services[$serviceName]) && class_exists($services[$serviceName])) {
            return new $services[$serviceName]();
        } else {
            return null;
        }
    }
}
