<?php

namespace tpaksu\LaravelOTPLogin\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use tpaksu\LaravelOTPLogin\OneTimePassword;
use tpaksu\LaravelOTPLogin\OneTimePasswordLog;

/**
 * Class for handling OTP view display and processing
 */
class OtpController extends Controller
{
    /**
     * Shows the OTP login screen
     *
     * @param  int  $id
     * @return View/Redirect
     */
    public function view(Request $request)
    {
        // this route is protected by WEB and AUTH middlewares, but still, this check can be useful.
        if (\Auth::check()) {

            // Check if user has already made a OTP request with a "waiting" status
            $otp = OneTimePassword::where([
                "user_id" => \Auth::user()->id,
                "status" => "waiting"
            ])->orderByDesc("created_at")->first();

            // if it exists
            if ($otp instanceof OneTimePassword) {

                // show the OTP validation form
                return view('laravel-otp-login::otpvalidate');
            } else {

                // the user hasn't done a request, why is he/she here? redirect back to login screen.
                \Auth::logout();
                return redirect('/')->withErrors(["username" => __("laravel-otp-login::messages.otp_expired")]);
            }
        } else {

            // the user hasn't tried to log in, why is he/she here? redirect back to login screen.
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
        // if user has been logged in
        if (\Auth::check()) {

            // get the user for querying the verification code
            $user = \Auth::user();

            // check if current request has a verification code
            if ($request->has("code")) {

                // get the code entered by the user to check
                $code = $request->input("code");

                // get the waiting verification code from database
                $otp = OneTimePassword::where([
                    "user_id" => $user->id,
                    "status" => "waiting"
                ])->orderByDesc("created_at")->first();

                // if the code exists
                if ($otp instanceof OneTimePassword) {

                    // compare it with the received code
                    if ($otp->checkPassword($code)) {

                        // the codes match, set a cookie to expire in one year
                        setcookie("otp_login_verified", "user_id_" . $user->id, time() + (365 * 24 * 60 * 60), "/", "", false, true);

                        // set the code status to "verified" in the database
                        $otp->acceptEntrance();

                        // redirect user to the login redirect path defined in the application

                        // get the application namespace
                        $namespace = \Illuminate\Container\Container::getInstance()->getNamespace();

                        // check if the stock login controller exists
                        $class = "\\" . $namespace . "Http\\Controllers\\Auth\\LoginController";
                        if (class_exists($class)) {

                            // create a new instance of this class to get the redirect path
                            $authenticator = new $class();

                            // redirect to the redirect after login page
                            return redirect($authenticator->redirectPath());
                        } else {

                            //redirect to the root page
                            return redirect("/");
                        }
                    } else {

                        // the codes don't match, return an error.
                        return redirect(route("otp.view"))->withErrors(["code" => __("laravel-otp-login::messages.code_mismatch")]);
                    }
                } else {

                    // the code doesn't exist in the database, return an error.
                    return redirect(route("login"))->withErrors(["phone" => __("laravel-otp-login::messages.otp_expired")]);
                }
            } else {

                // the code is missing, what should we compare to?
                return redirect(route("otp.view"))->withErrors(["code" => __("laravel-otp-login::messages.code_missing")]);
            }
        } else {

            // why are you here? we don't have anything to serve to you.
            return redirect(route("login"));
        }
    }
}
