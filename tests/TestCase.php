<?php

namespace tpaksu\LaravelOTPLogin\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use tpaksu\LaravelOTPLogin\Tests\Helpers\User;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }

    /**
     * Define environment setup.
     *
     * @param  Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        Config::set('database.default', 'testbench');
        Config::set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        Config::set('otp.user_class', User::class);
        Config::set('auth.providers.users.model', User::class);
        Config::set('otp.otp_default_service', 'mock');
        Config::set('otp.services.mock', [
            'class' => \tpaksu\LaravelOTPLogin\Tests\Helpers\MockService::class,
        ]);
        Config::set('view.paths', [__DIR__.'/views']);
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \tpaksu\LaravelOTPLogin\OTPServiceProvider::class,
            \Orchestra\Database\ConsoleServiceProvider::class,
        ];
    }
}
