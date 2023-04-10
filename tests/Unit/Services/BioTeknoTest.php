<?php

namespace tpaksu\LaravelOTPLogin\Tests\Unit\Services;

use tpaksu\LaravelOTPLogin\Tests\TestCase;
use tpaksu\LaravelOTPLogin\Tests\Helpers\User;
use tpaksu\LaravelOTPLogin\Services\BioTekno;

class BioTeknoTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        app('config')->set('otp', [
            'user_phone_field' => 'phone',
            'services' => [
                'biotekno' => [
                    'username' => 'mock_username',
                    'password' => 'mock_password',
                    'transmission_id' => 'mock_trx_id',
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
        $instance = $this->getMockBuilder(BioTekno::class)->setMethods(['sendRequest', 'buildURL'])->getMock();
        $instance->expects($this->once())
            ->method('buildURL')
            ->with(
                'mock_username',
                'mock_password',
                'dummy_phone',
                'mock_trx_id',
                '123456',
                trans('laravel-otp-login::messages.otp_message')
            )
            ->willReturn('test_url');
        $instance->expects($this->once())
            ->method('sendRequest')
            ->with('test_url')
            ->willReturn('Status=0');
        $result = $instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertTrue($result);
    }

    public function testSendMessageException()
    {
        $instance = $this->getMockBuilder(BioTekno::class)->setMethods(['sendRequest', 'buildURL'])->getMock();
        $instance->expects($this->once())
            ->method('buildURL')
            ->with(
                'mock_username',
                'mock_password',
                'dummy_phone',
                'mock_trx_id',
                '123456',
                trans('laravel-otp-login::messages.otp_message')
            )
            ->willThrowException(new \Exception());
        $instance->expects($this->never())
            ->method('sendRequest')
            ->with('test_url')
            ->willReturn('Status=0');
        $result = $instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertFalse($result);
    }


    public function testSendMessageNoPhone()
    {
        $this->user->phone = '';
        $instance = $this->getMockBuilder(BioTekno::class)->setMethods(['sendRequest', 'buildURL'])->getMock();
        $instance->expects($this->never())
            ->method('buildURL');
        $instance->expects($this->never())
            ->method('sendRequest');
        $result = $instance->sendOneTimePassword($this->user, "123456", "654321");
        $this->assertFalse($result);
    }

    public function testBuildURL()
    {
        $result = (new BioTekno())->buildURL(
            'mock_username',
            'mock_password',
            'mock_phone',
            'mock_transmission_id',
            'mock_otp',
            'mock_message_:password'
        );
        $this->assertSame($result, "http://www.biotekno.biz:8080/SMS-Web/HttpSmsSend?Username=mock_username&" .
        "Password=mock_password&Msisdns=mock_phone&TransmissionID=mock_transmission_id&Messages=mock_message_mock_otp");
    }
}
