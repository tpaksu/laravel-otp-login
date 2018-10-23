<?php

//Route::group(['middleware' => ['auth']], function () {
    // impersonation
    Route::get('/login/otp/check', function (Request $request) {
        return view("laravel-otp-login::auth.otpvalidate");
    })->name("otp.login");
//});
