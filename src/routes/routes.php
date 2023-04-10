<?php

use Illuminate\Support\Facades\Route;
use tpaksu\LaravelOTPLogin\Controllers\OtpController;

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/login/verify', [OtpController::class, 'view'])->name("otp.view");
    Route::post('/login/check', [OtpController::class, 'check'])->name("otp.verify");
});
