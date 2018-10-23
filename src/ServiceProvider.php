<?php

namespace tpaksu\LaravelOTPLogin;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations', "laravel-otp-login");
        $this->loadRoutesFrom(__DIR__ . '/routes/routes.php', "laravel-otp-login");
        $this->loadViewsFrom(__DIR__ . '/views', 'laravel-otp-login');
        $this->publishes([__DIR__ . '/config/config.php' => config_path('otp-login.php')], "config");
        $this->publishes([__DIR__ . '/migrations' => database_path('migrations')], "migrations");
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
