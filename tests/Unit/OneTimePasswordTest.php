<?php

namespace tpaksu\LaravelOTPLogin\Tests\Unit;

use tpaksu\LaravelOTPLogin\OneTimePassword;
use tpaksu\LaravelOTPLogin\OneTimePasswordLog;
use tpaksu\LaravelOTPLogin\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class OneTimePasswordTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::table('one_time_passwords')->truncate();
        DB::table('one_time_password_logs')->truncate();
    }

    public function testCreate()
    {
        $otp = OneTimePassword::create([
            "user_id" => 1,
            "status" => "waiting",
        ]);
        $this->assertInstanceOf(OneTimePassword::class, $otp);
        $this->assertEquals($otp->user_id, 1);
        $this->assertEquals($otp->status, "waiting");
    }

    public function testRelations()
    {
        $otp = OneTimePassword::create([
            "user_id" => 1,
            "status" => "waiting",
        ]);
        OneTimePasswordLog::create([
            'user_id' => 1,
            'otp_code' => "1234",
            'refer_number' => "123456",
            'status' => 'waiting']);
        OneTimePasswordLog::create([
            'user_id' => 2, 'otp_code' => "1234", 'refer_number' => "123456", 'status' => 'waiting']);
        OneTimePasswordLog::create([
            'user_id' => 1, 'otp_code' => "1235", 'refer_number' => "123457", 'status' => 'discarded']);
        OneTimePasswordLog::create([
            'user_id' => 1, 'otp_code' => "1236", 'refer_number' => "123458", 'status' => 'discarded']);
        $this->assertEquals(3, $otp->oneTimePasswordLogs()->count());
        $this->assertEquals(1, $otp->oneTimePasswordLogs()->where(["status" => "waiting"])->count());
        $this->assertEquals(2, $otp->oneTimePasswordLogs()->where(["status" => "discarded"])->count());
        $this->assertEquals(0, $otp->oneTimePasswordLogs()->where(["status" => "expired"])->count());
    }
}
