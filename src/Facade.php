<?php namespace tpaksu\LaravelOTPLogin;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return OneTimePassword::class;
    }
}
