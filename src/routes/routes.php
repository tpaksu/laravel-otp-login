<?php

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/login/verify', function (Request $request) {
        dd("taha");
        return view("laravel-otp-login::otpvalidate");
    })->name("otp.login");
    Route::get('/login/check', function (Request $request) {

    })->name("otp.check");
});
