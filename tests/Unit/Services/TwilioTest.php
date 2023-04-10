<?php

namespace tpaksu\LaravelOTPLogin\Tests\Unit\Services;

use tpaksu\LaravelOTPLogin\Tests\TestCase;
use tpaksu\LaravelOTPLogin\Tests\Helpers\User;
use tpaksu\LaravelOTPLogin\Services\Twilio;

class TwilioTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        app('config')->set('otp', [
            'user_phone_field' => 'phone',
            'services' => [
                'twilio' => [
                    'account_sid' => 'mock_account_sid',
                    'auth_token' => 'mock_auth_token',
                    'from' => 'mock_from',
                ],
            ],
        ]);
        $this->user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
    }

    public function testSendMessage()
    {
        $instance = $this->getMockBuilder(Twilio::class)->setMethods(['sendRequest'])->getMock();
        $instance->expects($this->once())
            ->method('sendRequest')
            ->with('https://api.twilio.com/2010-04-01/Accounts/mock_account_sid/Messages.json', 'dummy_phone', '123456')
            ->willReturn('"status": "queued",');
        $result = $instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertTrue($result);
    }

    public function testSendMessageException()
    {
        $instance = $this->getMockBuilder(Twilio::class)->setMethods(['sendRequest'])->getMock();
        $instance->expects($this->once())
            ->method('sendRequest')
            ->with('https://api.twilio.com/2010-04-01/Accounts/mock_account_sid/Messages.json', 'dummy_phone', '123456')
            ->willThrowException(new \Exception());
        $result = $instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertFalse($result);
    }

    public function testSendMessageNoPhone()
    {
        $this->user->phone = '';
        $instance = $this->getMockBuilder(Twilio::class)->setMethods(['sendRequest'])->getMock();
        $instance->expects($this->never())
            ->method('sendRequest');
        $result = $instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertFalse($result);
    }
}
