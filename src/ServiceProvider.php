<?php

namespace tpaksu\LaravelOTPLogin;

use tpaksu\LaravelOTPLogin\LoginMiddleware;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/routes.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'laravel-otp-login');

        $this->publishes([__DIR__ . '/config/config.php' => config_path('otp-login.php')], "config");
        $this->publishes([__DIR__.'/views/auth' => resource_path('views/vendor/laravel-otp-login')], "views");
        $this->publishes([__DIR__ . '/migrations' => database_path('migrations')], "migrations");

        $this->app['router']->pushMiddlewareToGroup('web', LoginMiddleware::class);

    }
}
