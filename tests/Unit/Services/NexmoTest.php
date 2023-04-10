<?php

namespace tpaksu\LaravelOTPLogin\Tests\Unit\Services;

use tpaksu\LaravelOTPLogin\Tests\TestCase;
use tpaksu\LaravelOTPLogin\Tests\Helpers\User;
use tpaksu\LaravelOTPLogin\Services\Nexmo;

class NexmoTest extends TestCase
{
    protected $user;
    protected $instance;

    public function setUp(): void
    {
        parent::setUp();
        app('config')->set('otp', [
            'user_phone_field' => 'phone',
            'services' => [
                'nexmo' => [
                    'api_key' => 'mock_api_key',
                    'api_secret' => 'mock_api_secret',
                    'from' => 'mock_from',
                ],
            ],
        ]);
        $this->user = User::create([
            'email' => 'dummy@email.com',
            'phone' => 'dummy_phone',
        ]);
        if (version_compare(app()->version(), '10.0', '>=')) {
            $this->instance = $this->getMockBuilder(Nexmo::class)
            ->onlyMethods(['sendRequest', 'buildURL'])
            ->getMock();
        } else {
            $this->instance = $this->getMockBuilder(Nexmo::class)
            ->setMethods(['sendRequest', 'buildURL'])
            ->getMock();
        }
    }
    public function testSendMessage()
    {
        $this->instance->expects($this->once())
            ->method('buildURL')
            ->with(
                'mock_api_key',
                'mock_api_secret',
                'dummy_phone',
                'mock_from',
                '123456',
                trans('laravel-otp-login::messages.otp_message')
            )
            ->willReturn('test_url');
        $this->instance->expects($this->once())
            ->method('sendRequest')
            ->with('test_url')
            ->willReturn('"status": "0",');
        $result = $this->instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertTrue($result);
    }

    public function testSendMessageException()
    {
        $this->instance->expects($this->once())
            ->method('buildURL')
            ->with(
                'mock_api_key',
                'mock_api_secret',
                'dummy_phone',
                'mock_from',
                '123456',
                trans('laravel-otp-login::messages.otp_message')
            )
            ->willThrowException(new \Exception());
        $this->instance->expects($this->never())
            ->method('sendRequest')
            ->with('test_url')
            ->willReturn('"status": "0",');
        $result = $this->instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertFalse($result);
    }

    public function testSendMessageNoPhone()
    {
        $this->user->phone = '';
        $this->instance->expects($this->never())
            ->method('buildURL');
        $this->instance->expects($this->never())
            ->method('sendRequest');
        $result = $this->instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertFalse($result);
    }

    public function testBuildURL()
    {
        $result = (new Nexmo())->buildURL(
            'mock_api_key',
            'mock_api_secret',
            'mock_phone',
            'mock_from',
            'mock_otp',
            'mock_message_:password'
        );
        $this->assertSame($result, "https://rest.nexmo.com/sms/json?api_key=mock_api_key&" .
        "api_secret=mock_api_secret&to=mock_phone&from=mock_from&text=mock_message_mock_otp");
    }
}
