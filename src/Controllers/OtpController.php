<?php

namespace tpaksu\LaravelOTPLogin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use tpaksu\LaravelOTPLogin\OneTimePassword;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class for handling OTP view display and processing
 */
class OtpController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Shows the OTP login screen
     *
     * @return mixed
     */
    public function view()
    {
        // This route is protected by "auth" middleware, so, no need to check authentication.
        $user = Auth::user();
        $usersColumn = config("otp.user_id_field", "id");
        $userId = $user->{$usersColumn};

        // Check if user has already made a OTP request with a "waiting" status
        $otp = OneTimePassword::where([
            "user_id" => $userId,
            "status" => "waiting"
        ])->orderByDesc("created_at")->first();

        // if OTP with a waiting status exists
        if ($otp instanceof OneTimePassword) {
            // show the OTP validation form.
            return view('laravel-otp-login::otpvalidate');
        } else {
            // Refresh the login attempt, must be mistakenly here.
            Auth::logout();
            return redirect('/')->withErrors(["username" => __("laravel-otp-login::messages.otp_expired")]);
        }
    }

    /**
     * Checks the given OTP
     *
     * @param Request $request
     * @return Redirector|RedirectResponse
     */
    public function check(Request $request)
    {
            // get the user for querying the verification code
            $user = Auth::user();
            $usersColumn = config("otp.user_id_field", "id");
            $userId = $user->{$usersColumn};

            // check if current request has a verification code
        if ($request->has("code")) {
            // get the code entered by the user to check
            $code = $request->input("code");

            // get the waiting verification code from database
            $otp = OneTimePassword::where([
                "user_id" => $userId,
                "status" => "waiting"
            ])->orderByDesc("created_at")->first();

            // if the code exists
            if ($otp instanceof OneTimePassword) {
                // compare it with the received code
                if ($otp->checkPassword($code)) {
                    // the codes match, set a cookie to expire in one year
                    setcookie(
                        "otp_login_verified",
                        "user_id_" . $userId,
                        time() + (365 * 24 * 60 * 60),
                        "/",
                        "",
                        false,
                        true
                    );

                    // set the code status to "verified" in the database
                    $otp->acceptEntrance();

                    // redirect user to the login redirect path defined in the application

                    // get the application namespace
                    $namespace = app()->getNamespace();

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
                    return redirect(route("otp.view"))
                        ->withErrors(["code" => __("laravel-otp-login::messages.code_mismatch")]);
                }
            } else {
                // the code doesn't exist in the database, return an error.
                return redirect(route("login"))
                    ->withErrors(["phone" => __("laravel-otp-login::messages.otp_expired")]);
            }
        } else {
            // the code is missing, what should we compare to?
            return redirect(route("otp.view"))
                ->withErrors(["code" => __("laravel-otp-login::messages.code_missing")]);
        }
    }
}
