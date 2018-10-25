<?php

namespace tpaksu\LaravelOTPLogin\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use tpaksu\LaravelOTPLogin\OneTimePassword;
use tpaksu\LaravelOTPLogin\OneTimePasswordLog;

class OtpController extends Controller
{
    /**
     * Shows the otp login screen
     *
     * @param  int  $id
     * @return View/Redirect
     */
    public function view(Request $request)
    {
        // this route is protected by WEB and AUTH middlewares, but still, this check can be useful.

        if (\Auth::check()) {
            $otp = OneTimePassword::where(["user_id" => \Auth::user()->id, "status" => "waiting"])->orderByDesc("created_at")->first();
            if ($otp instanceof OneTimePassword) {
                return view('laravel-otp-login::otpvalidate');
            } else {
                \Auth::logout();
                return redirect('/')->withErrors(["username" => "Your OTP Session seems to be expired. Please login again."]);
            }
        } else {
            return redirect('/')->withErrors(["username" => "You are not allowed to do this."]);;
        }
    }

    /**
     * Checks the given OTP
     *
     * @param Request $request
     * @return void
     */
    public function check(Request $request)
    {
        $user = \Auth::user();
        $code = $request->input("code");
        if (!$code) {
            return redirect(route("otp.view"))->withErrors(["code" => __("The field should exist.")]);
        } else {
            $otp = OneTimePassword::where(["user_id" => $user->id, "status" => "waiting"])->orderByDesc("created_at")->first();
            if ($otp instanceof OneTimePassword) {
                if ($otp->checkPassword($code)) {
                    $otp->acceptEntrance();
                    return redirect("/");
                } else {
                    return redirect(route("otp.view"))->withErrors(["code" => __("The code doesn't match to the generated one. Please check and re-enter the code you received.")]);
                }
            } else {
                return redirect(route("login"))->withErrors(["phone" => __("Your OTP session has been expired. Please login again to get a new OTP code.")]);
            }
        }
    }
}
