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

class OTPControllerTest extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        DB::table('users')->truncate();
        DB::table('one_time_passwords')->truncate();
        DB::table('one_time_password_logs')->truncate();
        $this->user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        Route::middleware([LoginMiddleware::class, 'auth'])->get('/logout', function () {
            return "logout form";
        })->name('logout');
        Route::middleware([LoginMiddleware::class, 'auth'])->get('/login', function () {
            return "login form";
        })->name('login');
    }

    public function testRendersViewWhenOTPExistsAndWaitingForVerification()
    {
        $otp = OneTimePassword::create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
         ]);
        $response = $this->actingAs($this->user, "web")->get(route("otp.view"));
        $response->assertSeeText(__("laravel-otp-login::messages.verify_phone_title"));
    }

    public function testRedirectsToHomeWhenOTPDoesntExist()
    {
        $response = $this->actingAs($this->user, "web")->get(route("otp.view"));
        $response->assertStatus(302);
        $response->assertRedirect('/');
        $response->assertSessionHasErrors(["username" => __("laravel-otp-login::messages.otp_expired")]);
    }

    public function testCheckMethodWithoutOTPRecordAndCode()
    {
        $response = $this->actingAs($this->user)->post(route('otp.verify'), []);
        $response->assertStatus(302);
        $response->assertRedirect('/login/verify');
    }

    public function testCheckMethodWithoutOTPCode()
    {
        $otp = OneTimePassword::create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
         ]);
        $response = $this->actingAs($this->user)->post(route('otp.verify'), []);
        $response->assertStatus(302);
        $response->assertRedirect('/login/verify');
    }

    public function testCheckMethodWithoutOTPRecordWithCode()
    {
        $response = $this->actingAs($this->user)->post(route('otp.verify'), ['code' => '123456']);
        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(["phone" => __("laravel-otp-login::messages.otp_expired")]);
    }

    public function testCheckMethodWithOTPRecordWithoutLog()
    {
        $otp = OneTimePassword::create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
         ]);
        $response = $this->actingAs($this->user)->post(route('otp.verify'), ['code' => 'invalid']);
        $response->assertStatus(302);
        $response->assertRedirect('/login/verify');
        $response->assertSessionHasErrors(["code" => __("laravel-otp-login::messages.code_mismatch")]);
    }

    public function testCheckMethodWithOTPRecordAndInvalidCode()
    {
        $otp = OneTimePassword::create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
         ]);
         $otp->oneTimePasswordLogs()->create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
            'refer_number' => '654321',
            'otp_code' => '123456',
         ]);
        $response = $this->actingAs($this->user)->post(route('otp.verify'), ['code' => 'invalid']);
        $response->assertStatus(302);
        $response->assertRedirect('/login/verify');
        $response->assertSessionHasErrors(["code" => __("laravel-otp-login::messages.code_mismatch")]);
    }

    public function testCheckMethodWithOTPRecordAndValidCode()
    {
        $otp = OneTimePassword::create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
         ]);
         $otp->oneTimePasswordLogs()->create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
            'refer_number' => '654321',
            'otp_code' => '123456',
         ]);
        $response = $this->actingAs($this->user)->post(route('otp.verify'), ['code' => '123456']);
        $response->assertStatus(302);
        $response->assertRedirect('/');
    }

    public function testCheckMethodWithOTPRecordAndValidCodeEncrypted()
    {
        app('config')->set('otp.encode_password', true);
        $otp = OneTimePassword::create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
         ]);
         $otp->oneTimePasswordLogs()->create([
            'user_id' => $this->user->id,
            'status' => 'waiting',
            'refer_number' => '654321',
            'otp_code' => Hash::make('123456'),
         ]);
        $response = $this->actingAs($this->user)->post(route('otp.verify'), ['code' => '123456']);
        $response->assertStatus(302);
        $response->assertRedirect('/');
    }
}
