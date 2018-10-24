<?php

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/login/verify', 'tpaksu\LaravelOTPLogin\Controllers\OtpController@view')->name("otp.view");
    Route::post('/login/check', 'tpaksu\LaravelOTPLogin\Controllers\OtpController@check' )->name("otp.verify");
});
