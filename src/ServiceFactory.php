<?php
namespace tpaksu\LaravelOTPLogin;

use tpaksu\LaravelOTPLogin\Services;

class ServiceFactory
{
    public function getService($serviceName)
    {
        $services = config("otp.services", []);
        if (isset($services[$serviceName]) && isset($services[$serviceName]["class"]) && class_exists($services[$serviceName]["class"])) {
            return new $services[$serviceName]["class"]();
        } else {
            return null;
        }
    }
}
