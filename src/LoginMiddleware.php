<?php

namespace tpaksu\LaravelOTPLogin;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class LoginMiddleware
{
    /**
     * Enables debug logging
     *
     * @var boolean
     */
    private $debug = false;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->debuglog("entered middleware");
        $this->debuglog($request->route()->middleware());

        // check if the request should be bypassed, or the request doesn't have authentication required
        $routeMiddleware = $request->route()->middleware();
        if ($this->bypassing()|| ! in_array("auth", $routeMiddleware, true)) {
            $this->debuglog("bypassing");
            return $next($request);
        }

        // get the current route
        $routeName = $request->route()->getName();
        $this->debuglog("routename $routeName");

        // check if the requested route should be checked against OTP verification status
        // and also for the user login status
        // this is needed for skipping the OTP and login routes
        if ($this->willCheck($routeName)) {
            $this->debuglog("willcheck = true");
            $user = Auth::user();
            $userIdField = config("otp.user_id_field", "id");
            $userId = $user->{$userIdField};

            // check for user OTP request in the database
            $otp = $this->getUserOTP($user);

            // a record exists for the user in the database
            if ($otp instanceof OneTimePassword) {
                $this->debuglog("otp found");

                // if has a pending OTP verification request
                if ($otp->status == "waiting") {
                    // check timeout
                    if ($otp->isExpired()) {
                        $this->debuglog("otp is expired");

                        // expired. expire the cookie if exists
                        $this->createExpiredCookie();
                        //  redirect to login page
                        return $this->logout($otp);
                    } else {
                        $this->debuglog("otp is valid, but status is waiting");

                        // still valid. redirect to login verify screen
                        return redirect(route("otp.view"));
                    }
                } elseif ($otp->status == "verified") {
                    $this->debuglog("otp is verified");

                    // verified request. go forth.
                    $response = $next($request);
                    if ($response->status() == 419) {
                        // timeout occured
                        $this->debuglog("timeout occured");
                        // expire the cookie if exists
                        $this->createExpiredCookie();
                        // redirect to login screen
                        return $this->logout($otp);
                    } else {
                        $this->debuglog("otp is valid, go forth");
                        // create a cookie that will expire in one year
                        $this->createCookie($userId);
                        // continue to next request
                        return $response;
                    }
                } else {
                    // invalid status, needs to login again.
                    $this->debuglog("invalid status");
                    // expire the cookie if exists
                    $this->createExpiredCookie();
                    // redirect to login page
                    return $this->logout($otp);
                }
            } else {
                $this->debuglog("otp doesn't exist");
                // creating a new OTP login session
                $otp = OneTimePassword::create([
                "user_id" => $userId,
                "status" => "waiting",
                ]);
                $this->debuglog("created otp for {$userId}");
                // send the OTP to the user
                if ($otp->send() == true) {
                    $this->debuglog("otp send succeeded");
                    // redirect to OTP verification screen
                    return redirect(route('otp.view'));
                } else {
                    $this->debuglog("otp send failed");
                    // otp send failed, expire the cookie if exists
                    $this->createExpiredCookie();
                    // send the user to login screen with error
                    return $this
                    ->logout($otp)
                    ->with(
                        "message",
                        __("laravel-otp-login::messages.service_not_responding")
                    );
                }
            }
        } else {
            $this->debuglog("willcheck failed");

            // if an active session doesn't exist, but a cookie is present
            if (Auth::guest() && $this->hasCookie()) {
                $this->debuglog("if user hasn't logged in and cookie exists, delete cookie");
                // get the user ID from cookie
                $userId = $this->getUserIdFromCookie();
                // delete the OTP requests from database for that specific user
                OneTimePassword::whereUserId($userId)->delete();
                // expire that cookie
                $this->createExpiredCookie();
            }
        }

        $this->debuglog("returning next request");

        // continue processing next request.
        return $next($request);
    }

    /**
     * Check if the service should bypass the checks
     *
     * @return boolean
     */
    private function bypassing()
    {
        return Session::has("otp_service_bypass") && Session::get("otp_service_bypass", false);
    }

    /**
     * Check if the given route should continue the OTP check
     *
     * @param string $routeName
     * @return boolean
     */
    private function willCheck($routeName)
    {
        return Auth::check()
        && config("otp.otp_service_enabled", false)
        && !in_array($routeName, ['otp.view', 'otp.verify', 'logout']);
    }

    /**
     * Get the active OTP for the given user
     *
     * @param Authenticatable $user
     * @return \tpaksu\LaravelOTPLogin\OneTimePassword
     */
    private function getUserOTP($user)
    {
        $idField = config("otp.user_id_field", "id");
        return OneTimePassword::whereUserId($user->{$idField})->where("status", "!=", "discarded")->first();
    }

    /**
     * Logs out the user with clearing the OTP records
     * @todo Logout redirects the user to home page, need to find a way to redirect it to the login page.
     *
     * @param \tpaksu\LaravelOTPLogin\OneTimePassword $otp
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    private function logout($otp)
    {
        $otp->discardOldPasswords();
        Auth::logout();
        return redirect('/');
    }

    /**
     * Checks if the cookie exists with an user id
     *
     * @return boolean
     */
    private function hasCookie()
    {
        return isset($_COOKIE["otp_login_verified"]) && Str::startsWith($_COOKIE["otp_login_verified"], 'user_id_');
    }

    /**
     * Sets the cookie with the user ID inside, active for one year
     *
     * @param int $user_id
     * @return void
     */
    private function createCookie($user_id)
    {
        setcookie("otp_login_verified", "user_id_" . $user_id, time() + (365 * 24 * 60 * 60));
    }

    /**
     * Expires the OTP login verified cookie
     *
     * @return void
     */
    private function createExpiredCookie()
    {
        if ($this->hasCookie()) {
            setcookie("otp_login_verified", "", time() - 100);
        }
    }

    /**
     * Gets the user ID from the OTP login verified cookie
     *
     * @return integer
     */
    private function getUserIdFromCookie()
    {
        return intval(str_replace("user_id_", "", $_COOKIE["otp_login_verified"]));
    }

    private function debuglog($message)
    {
        if ($this->debug) {
            logger($message);
        }
    }
}
