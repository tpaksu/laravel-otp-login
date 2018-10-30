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
                return redirect('/')->withErrors(["username" => __("laravel-otp-login::messages.otp_expired")]);
            }
        } else {
            return redirect('/')->withErrors(["username" => __("laravel-otp-login::messages.unauthorized")]);;
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
            return redirect(route("otp.view"))->withErrors(["code" => __("laravel-otp-login::messages.code_missing")]);
        } else {
            $otp = OneTimePassword::where(["user_id" => $user->id, "status" => "waiting"])->orderByDesc("created_at")->first();
            if ($otp instanceof OneTimePassword) {
                if ($otp->checkPassword($code)) {
                    // will expire in one year
                    setcookie("otp_login_verified", "user_id_" . $user->id, time() + (365 * 24 * 60 * 60), "/", "", false, true);
                    $otp->acceptEntrance();
                    return redirect("/");
                } else {
                    return redirect(route("otp.view"))->withErrors(["code" => __("laravel-otp-login::messages.code_mismatch")]);
                }
            } else {
                return redirect(route("login"))->withErrors(["phone" => __("laravel-otp-login::messages.otp_expired")]);
            }
        }
    }
}
