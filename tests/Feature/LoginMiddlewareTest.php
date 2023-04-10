<?php

namespace tpaksu\LaravelOTPLogin\Tests\Feature;

use tpaksu\LaravelOTPLogin\LoginMiddleware;
use tpaksu\LaravelOTPLogin\OneTimePassword;
use tpaksu\LaravelOTPLogin\Tests\TestCase;
use tpaksu\LaravelOTPLogin\Tests\Helpers\User;
use tpaksu\LaravelOTPLogin\Tests\Helpers\MockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\RouteCollection;

class LoginMiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->prepareRoutes();
        $this->prepareDB();
    }

    public function testRouteUsesWebAuthMiddleware()
    {
        $this->assertRouteUsesMiddleware(
            'test.route',
            [LoginMiddleware::class, 'auth']
        );
        $this->assertRouteUsesMiddleware(
            'test.route-no-auth',
            [LoginMiddleware::class]
        );
    }

    public function testBypassingWhenSessionExists()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $this->actingAs($user, 'web');
        $response = $this
            ->withSession(['otp_service_bypass' => true])
            ->get('/test-route');
        $response->assertStatus(200);
        $response->assertSeeText("logged in");
    }

    public function testBypassingWhenRouteNoAuth()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route-no-auth');
        $response->assertStatus(200);
        $response->assertSeeText("logged in no auth");
    }

    public function testWontCheckOnOTPScreen()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $this->actingAs($user, 'web');
        $request = Request::create('/login/verify', 'GET');
        $route = Route::getRoutes()->getByName('otp.verify');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
        $response = (new LoginMiddleware())->handle($request, function ($request) {
            return 'showing verify screen';
        });
        $this->assertEquals($response, 'showing verify screen');
    }

    public function testWontCheckOnOTPScreenGuestWithCookie()
    {
        $request = Request::create('/login/verify', 'GET');
        $route = Route::getRoutes()->getByName('otp.verify');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
        $response = (new LoginMiddleware())->handle($request, function ($request) {
            return 'showing verify screen';
        });
        $this->assertEquals($response, 'showing verify screen');
    }

    public function testRedirectsToOTPPageWhenCodeIsMissing()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('login/verify');
    }

    public function testRedirectsToOTPPageWhenCodeExists()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $otp = OneTimePassword::create([
           'user_id' => $user->id,
           'status' => 'waiting',
        ]);
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('login/verify');
    }

    public function testLogoutsWhenCodeExistsButExpired()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $otp = OneTimePassword::create([
           'user_id' => $user->id,
           'status' => 'waiting',
        ]);
        $otp->created_at = "2022-04-04 00:00:00";
        $otp->save();
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/');
    }

    public function testLogoutsWhenCodeExistsButHasInvalidStatus()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $otp = OneTimePassword::create([
           'user_id' => $user->id,
           'status' => 'invalid',
        ]);
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/');
    }

    public function testLogoutsWhenInvalidService()
    {
        app('config')->set('otp.otp_default_service', 'invalid');
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/');
        $this->assertEquals(Session::get('message'), __('laravel-otp-login::messages.service_not_responding'));
    }

    public function testLogoutsWhenSendingFailed()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        global $mockSMSServiceReturn;
        $mockSMSServiceReturn = false;
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/');
        $this->assertEquals(Session::get('message'), __('laravel-otp-login::messages.service_not_responding'));
    }

    public function testLogoutsWhenSendingFailedInDifferentLocale()
    {
        app()->setLocale('tr');
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        global $mockSMSServiceReturn;
        $mockSMSServiceReturn = false;
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/');
        $this->assertEquals(Session::get('message'), __('laravel-otp-login::messages.service_not_responding'));
        app()->setLocale('en');
    }

    public function testLogoutsWhenSessionExpired()
    {
        $this->prepareExpiredRoute();
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $otp = OneTimePassword::create([
           'user_id' => $user->id,
           'status' => 'verified',
        ]);
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/');
    }

    public function testExpiresCookieWhenSessionExpired()
    {
        $this->prepareExpiredRoute();
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        app('auth')->loginUsingId($user->id);
        // Keep cookie by disabling the feature for once.
        app('config')->set('otp.otp_service_enabled', false);
        app('auth')->logout();
        app('config')->set('otp.otp_service_enabled', true);
        $_COOKIE['otp_login_verified'] = 'user_id_1';
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertCookieMissing('otp_login_verified');
    }

    public function testExpiresCookieOnLogout()
    {
        $this->prepareExpiredRoute();
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        app('auth')->loginUsingId($user->id);
        app('auth')->logout();
        $_COOKIE['otp_login_verified'] = 'user_id_1';
        $response = $this->get('/test-route');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertCookieMissing('otp_login_verified');
    }

    public function testRedirectsToPageWhenOTPIsVerified()
    {
        $user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        $otp = OneTimePassword::create([
           'user_id' => $user->id,
           'status' => 'verified',
        ]);
        $this->actingAs($user, 'web');
        $response = $this->get('/test-route');
        $response->assertStatus(200);
        $this->assertEquals($response->getContent(), 'logged in');
    }

    private function prepareRoutes()
    {
        $router = app("router");
        Route::middleware([LoginMiddleware::class, 'auth'])->get('/test-route', function () {
            return "logged in";
        })->name('test.route');
        Route::middleware([LoginMiddleware::class, 'auth'])->get('/login', function () {
            return "login form";
        })->name('login');
        Route::middleware([LoginMiddleware::class])->get('/test-route-no-auth', function () {
            return "logged in no auth";
        })->name('test.route-no-auth');
        if (\method_exists(RouteCollection::class, 'compile')) {
            $router->getRoutes()->compile();
        }
    }

    private function prepareExpiredRoute()
    {
        Route::middleware([LoginMiddleware::class, 'auth'])->get('/test-route', function () {
            return response('Session expired', 419)->header('Content-Type', 'text/plain');
        })->name('test.route');
    }

    private function prepareDB()
    {
        Session::start();
        DB::table('one_time_passwords')->truncate();
        DB::table('one_time_password_logs')->truncate();
        DB::table('users')->truncate();
    }
}
