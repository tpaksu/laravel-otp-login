# Laravel OTP Login Package
[![Total Downloads](https://poser.pugx.org/tpaksu/laravel-otp-login/downloads.png)](https://packagist.org/packages/tpaksu/laravel-otp-login) [![Latest Stable Version](https://poser.pugx.org/tpaksu/laravel-otp-login/v/stable.png)](https://packagist.org/packages/tpaksu/laravel-otp-login) [![HitCount](http://hits.dwyl.com/tpaksu/laravel-otp-login.svg)](http://hits.dwyl.com/tpaksu/laravel-otp-login) [![](https://img.shields.io/github/languages/code-size/badges/shields.svg)](https://github.com/tpaksu/laravel-otp-login) [![](https://img.shields.io/packagist/l/tpaksu/laravel-otp-login.svg)](https://github.com/tpaksu/laravel-otp-login)


This package provides an One Time Password check step after successful login using the default authentication mechanism. The package stores all requested OTP and it's validation statuses in `one_time_passwords` and `one_time_password_logs` tables.

It uses the middleware included in this package to check if the user has passed the OTP check or not regarding the current authentication status.

## Credits

This package is based on the idea of [nattaponra/laravel-one-time-password](https://github.com/nattaponra/laravel-one-time-password) package. The middleware, translations, views, the controller, routes and extending the services strategy is developed by me.

## Requirements

* [Composer](https://getcomposer.org/)
* [Laravel](http://laravel.com/)


## Installation

You can install this package on an existing Laravel project with using composer:

```bash
 $ composer require tpaksu/laravel-otp-login
```

Then, register the OTP Login ServiceProvider editing **config/app.php** file and adding to providers array:

```php
  tpaksu\LaravelOTPLogin\OTPServiceProvider::class,
```

Note: use the following for Laravel <5.1 versions:

```php
 'tpaksu\LaravelOTPLogin\OTPServiceProvider',
```

Publish files with:

```bash
 $ php artisan vendor:publish --provider="tpaksu\LaravelOTPLogin\OTPServiceProvider"
```

or by using only `php artisan vendor:publish` and select the `tpaksu\LaravelOTPLogin\OTPServiceProvider` from the outputted list.

Apply the migrations for the `OneTimePassword` and `OneTimePasswordLogs` tables:

```bash
 $ php artisan migrate
```

## Configuration

This package publishes an `otp.php` file inside your applications's `config` folder which contains the settings for this package. Most of the variables are bound to environment variables, but you are free to directly edit this file, or add the configuration keys to the `.env` file.

This line shows if the service is enabled or not:

```php
'otp_service_enabled' => true,
```

On this line, you can configure the default SMS service the system will use to send the OTP SMS'es:

```php
'otp_default_service' => env("OTP_SERVICE", "nexmo"),
```

The services predefined in this package are `Nexmo`, `Twilio` and `BioTekno` for now, but it's very easy to add your custom service to this package. It'll be explained in detail in the [Services](#services) section of this documentation.

```php
'services' => [
    'biotekno' => [
        "class" => \tpaksu\LaravelOTPLogin\Services\BioTekno::class,
        "username" => env('OTP_USERNAME', null),
        "password" => env('OTP_PASSWORD', null),
        "transmission_id" => env('OTP_TRANSMISSION_ID', null)
    ],
    'nexmo' => [
        'class' => \tpaksu\LaravelOTPLogin\Services\Nexmo::class,
        'api_key' => env("OTP_API_KEY", null),
        'api_secret' => env('OTP_API_SECRET', null),
        'from' => env('OTP_FROM', null)
    ],
    'twilio' => [
        'class' => \tpaksu\LaravelOTPLogin\Services\Twilio::class,
        'account_sid' => env("OTP_ACCOUNT_SID", null),
        'auth_token' => env("OTP_AUTH_TOKEN", null),
        'from' => env("OTP_FROM", null)
    ]
],
```

This is very important. The service expects you to have a phone field in your `users` table or a related table to send the SMS to the user. If your user's phone number is in the `users` table, you can write the field name directly to this setting.

```php
'user_phone_field' => 'phone',
```

Otherwise, if it's in a table like `user_phones` linked to your `users` table, you can use the linked setting like this:

```php
'user_phone_field' => 'user_phone.phone',
```

This line shows the length of the generated one time password's reference number. See `otp_digit_length` setting description for limitations. It's not currently used in SMS but it's generated and saved to database. In later versions, I plan to implement it to the services.

```php
'otp_reference_number_length' => 6,
```

This defines the OTP validity timeout in seconds, currently set as 3 months.

```php
'otp_timeout' => 7890000,
```

This line shows the length of the generated one time password. It should be below 10 because of PHP's integer limit which is 2<sup>32</sup> (2,147,483,647) on 32-bit machines. It'll be more configurable in the later versions, but I don't think it'll be needed more than 10 digits for UX reasons.

```php
'otp_digit_length' => 6,
```

## Views

This package publishes one view named `otpvalidate.blade.php` to `resources/views/vendor/laravel-otp-login` folder, which contains the OTP validation screen. The strings used in this view are fetched from the language files also published in this package, so if you are trying to change the strings inside this view, you can change it inside the language file.

## Translations

This package publishes the translations to `resources/lang/vendor/laravel-otp-login` folder, Turkish and English languages are published by this package as `php` files, and you can translate it into more languages by using the English language file as a template. Here's the content of the English file as an example:

```php
<?php return [
    "verify_phone_title" => "Verify Your Phone Number",
    "verify_phone_button" => "Verify",
    "one_time_password" => "One Time Password (OTP)",
    "hero_text" => "Thanks for giving your details. An OTP has been sent to your Mobile Number. Please enter the :digit digit OTP below for Successful Login",
    "cancel_otp" => "Nevermind, take me back to the login page.",
    "otp_expired" => "Your OTP code seems to be expired. Please login again to get a new OTP code.",
    "unauthorized" => "You are not allowed to do this.",
    "code_missing" => "The OTP field should exist and be filled.",
    "code_mismatch" => "The code doesn't match to the generated one. Please check and re-enter the code you received.",
    "otp_message" => "Your one-time password to enter the system is: :password",
    "otp_not_received" => "Didn't receive the password? Log out and try again!",
    "service_not_responding" => "SMS service currently seems to be not responding. Please try again."
];
```

## Services
### Included services

#### BioTekno

[BioTekno](http://www.biotekno.com.tr/en/) was the initial service that I used to develop this package for my company, so I decided to leave it here. The service uses a `GET` request to send SMS and requires username/password combination with a transmission ID which is used as the name displayed instead the phone number on it's customer's mobile device. It uses `tpaksu\LaravelOTPLogin\Services\BioTekno` class to send the message.

```php
    'biotekno' => [
        "class" => \tpaksu\LaravelOTPLogin\Services\BioTekno::class,
        "username" => env('OTP_USERNAME', null),
        "password" => env('OTP_PASSWORD', null),
        "transmission_id" => env('OTP_TRANSMISSION_ID', null)
    ],
```

#### Nexmo

[Nexmo](https://www.nexmo.com/) seemed to be one of the popular messaging service, as I'm not much familiar with SMS services before, but It has an easy API to work with, so I decided to implement it inside this package. It has it's own composer package to use in Laravel or another PHP system, but since I was only interested in sending SMS, I directly implemented their REST API solution in this package.

The service provides you with an `api_key` and an `api_secret` as common API authentication parameters, but you will also require a phone number (again, provided by Nexmo) to use as a sender address. And this parameters will be enough to configure the service. It uses `tpaksu\LaravelOTPLogin\Services\Nexmo` class to send the message.


```php
    'nexmo' => [
        'class' => \tpaksu\LaravelOTPLogin\Services\Nexmo::class,
        'api_key' => env("OTP_API_KEY", null),
        'api_secret' => env('OTP_API_SECRET', null),
        'from' => env('OTP_FROM', null)
    ],
```

#### Twilio
[Twilio](https://www.twilio.com) is also one of the most popular messaging service, providing also voice calls, social messging and video calls besides SMS messaging. And also has it's own libraries that can be used on several PHP based frameworks/software like Laravel, but I still choose the easy way and implemented only the REST API style of sending messages. If you look at the source of `tpaksu\LaravelOTPLogin\Services\Twilio`, you'll understand.

The Twilio service provides you an `account_sid` , an `auth_token` on it's console after you finish the registration and create a SMS project. Then you'll need a phone number like Nexmo to send an SMS using these information to your "verified" numbers. And you also need to enable the country on the console you wish to send messages to. Otherwise you'll get an error saying that the country is not permitted to send the SMS to. To the `from` config parameter, you need to fill in the phone number you've got from the service.

```php
    'twilio' => [
        'class' => \tpaksu\LaravelOTPLogin\Services\Twilio::class,
        'account_sid' => env("OTP_ACCOUNT_SID", null),
        'auth_token' => env("OTP_AUTH_TOKEN", null),
        'from' => env("OTP_FROM", null)
    ]
```

### Writing your own service

The service classes have this structure:

```php
<?php

// the package namespace, you won't be using it
// because you'll generate your service in the App\OTPService directory
namespace tpaksu\LaravelOTPLogin\Services;

// - OR if you are writing your service in App\OTPServices folder
namespace App\OTPServices;

// Your user instance, change it if you are using a different model
use App\User;

// this is a must.
use tpaksu\LaravelOTPLogin\ServiceInterface;

// the class name definition
class MyMessagingService implements ServiceInterface
{
    // this is where you define the global variables which you
    // fetch in the constructor to use in the method ;)

    private $api_key;
    private $phone_column;
    private $message;

    /**
     * constructor
     */
    public function __construct()
    {
        // this is where you get the global variables inside the class
        // - OR -
        // you might fetch them directly inside the sendOneTimePassword

        $this->api_key = config("otp.services.mymsgservice.api_key", null);
        $this->message = trans('laravel-otp-login::messages.otp_message');
        $this->phone_column = config('otp.user_phone_field');
    }

    /**
     * Sends the generated password to the user and returns if it's successful
     *
     * @param App\User $user : The user to send the OTP to
     * @param string $otp    : The One Time Password
     * @param string $ref    : The reference number of this request
     * @return boolean
     */
    public function sendOneTimePassword(User $user, $otp, $ref)
    {
        // this is where you send the $otp password SMS to the $user
        // get the phone number
        $phone_number = data_get($user, $this->phone_column, false);
        // if phone number doesn't exists return
        if($phone_number == false) return false;

        try {
            // send the SMS to the phone number here
            // get the response and if the transmission is succeeded return true,
            // otherwise return false
            return $result;
        }
        catch(\Exception $ex)
        {
            // always return false if something is wrong.
        }
    }
}

```



### Modifying existing services

If you need to modify a service included in this package, you can check your `App\OTPServices` folder and you'll see a copy of each service class in this folder. Change the namespaces from `tpaksu\LaravelOTPLogin\Services` to `App\OTPServices` and in the configuration file, change the class path to the modified file. This will be enough to use the modified version of this class.


## License

The MIT License (MIT)

Copyright (c) 2018

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
