<?php

namespace tpaksu\LaravelOTPLogin;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use tpaksu\LaravelOTPLogin\LoginMiddleware;

class OTPServiceProvider extends \Illuminate\Support\ServiceProvider
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
        $this->loadTranslationsFrom(__DIR__ . '/translations', 'laravel-otp-login');
        $this->addCleanupOnLogout();

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/config/config.php' => config_path('otp.php')], "config");
            $this->publishes([__DIR__ . '/views' => resource_path('views/vendor/laravel-otp-login')], "views");
            $this->publishes([__DIR__ . '/migrations' => database_path('migrations')], "migrations");
            $this->publishes([__DIR__ . '/services' => app_path('OtpServices')], "migrations");
            $this->publishes([__DIR__ . '/translations' => resource_path('lang/vendor/laravel-otp-login')]);
        }

        // Register the middleware manually.
        app('router')->pushMiddlewareToGroup('web', LoginMiddleware::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'otp');
    }

    private function addCleanupOnLogout()
    {
        Event::listen(Logout::class, function (Logout $event) {
            // OTP cleanup on logout. Skip if feature not enabled.
            if (config("otp.otp_service_enabled", false) == false) {
                return;
            }

            $userIdField = config("otp.user_id_field", "id");
            $userId = $event->user->{$userIdField};

            // Clear cookies for the user.
            setcookie("otp_login_verified", "", time() - 3600);
            unset($_COOKIE['otp_login_verified']);

            // Discard all previous OTP codes.
            OneTimePassword::where("user_id", $userId)->get()->each(function ($otp) {
                $otp->discardOldPasswords();
                Session::forget("otp_service_bypass");
            });
        });
    }
}
