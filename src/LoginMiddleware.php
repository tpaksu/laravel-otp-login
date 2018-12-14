<?php

namespace tpaksu\LaravelOTPLogin;

use Closure;
use \Carbon\Carbon;
use Illuminate\Foundation\Auth\RedirectsUsers;

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
        if ($this->debug) logger("entered middleware");
        if ($this->debug) logger($request->route()->computedMiddleware);

        // check if the request should be bypassed, or the request doesn't have authentication required
        if ($this->bypassing() || in_array("auth", $request->route()->computedMiddleware) == false) {
            if ($this->debug) logger("bypassing");
            return $next($request);
        }

        // get the current route
        $routeName = $request->route()->getName();
        if ($this->debug) logger("routename $routeName");

        // check if the requested route should be checked against OTP verification status
        // and also for the user login status
        // this is needed for skipping the OTP and login routes

        if ($this->willCheck($routeName)) {
            if ($this->debug) logger("willcheck = true");

            // get the logged in user
            $user = \Auth::user();

            // check for user OTP request in the database
            $otp = $this->getUserOTP($user);

            // define the flag for refreshing the OTP verification code
            $needsRefresh = false;

            // a record exists for the user in the database
            if ($otp instanceof OneTimePassword) {

                if ($this->debug) logger("otp found");

                // if has a pending OTP verification request
                if ($otp->status == "waiting") {

                    // check timeout
                    if ($otp->isExpired()) {
                        if ($this->debug) logger("otp is expired");

                        // expired. expire the cookie if exists
                        $this->createExpiredCookie();

                        //  redirect to login page
                        return $this->logout($otp);
                    } else {
                        if ($this->debug) logger("otp is valid, but status is waiting");

                        // still valid. redirect to login verify screen
                        return redirect(route("otp.view"));
                    }
                } else if ($otp->status == "verified") {
                    if ($this->debug) logger("otp is verified");

                    // verified request. go forth.
                    $response = $next($request);
                    if ($response->status() == 419) {

                        // timeout occured
                        if ($this->debug) logger("timeout occured");

                        // expire the cookie if exists
                        $this->createExpiredCookie();

                        // redirect to login screen
                        return $this->logout($otp);
                    } else {
                        if ($this->debug) logger("otp is valid, go forth");

                        // create a cookie that will expire in one year
                        $this->createCookie($user->id);

                        // continue to next request
                        return $response;
                    }
                } else {
                    // invalid status, needs to login again.
                    if ($this->debug) logger("invalid status");

                    // expire the cookie if exists
                    $this->createExpiredCookie();

                    // redirect to login page
                    return $this->logout($otp);
                }
            } else {
                if ($this->debug) logger("otp doesn't exist");

                // creating a new OTP login session
                $otp = OneTimePassword::create([
                    "user_id" => $user->id,
                    "status" => "waiting",
                ]);
                if ($this->debug) logger("created otp for {$user->id}");

                // send the OTP to the user
                if ($otp->send() == true) {
                    if ($this->debug) logger("otp send succeeded");

                    // redirect to OTP verification screen
                    return redirect(route('otp.view'));
                } else {
                    if ($this->debug) logger("otp send failed");

                    // otp send failed, expire the cookie if exists
                    $this->createExpiredCookie();

                    // send the user to login screen with error
                    return $this->logout($otp)->with("message", __("laravel-otp-login::messages.service_not_responding"));
                }
            }
        } else {
            if ($this->debug) logger("willcheck failed");
            // if an active session doesn't exist, but a cookie is present
            if (\Auth::guest() && $this->hasCookie()) {

                if ($this->debug) logger("if user hasn't logged in and cookie exists, delete cookie");

                // get the user ID from cookie
                $user_id = $this->getUserIdFromCookie();

                // delete the OTP requests from database for that specific user
                OneTimePassword::whereUserId($user_id)->delete();

                // expire that cookie
                $this->createExpiredCookie();
            }
        }

        if ($this->debug) logger("returning next request");

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
        return \Session::has("otp_service_bypass") && \Session::get("otp_service_bypass", false);
    }

    /**
     * Check if the given route should continue the OTP check
     *
     * @param string $routeName
     * @return boolean
     */
    private function willCheck($routeName)
    {
        return \Auth::check() && config("otp.otp_service_enabled", false) && !in_array($routeName, ['otp.view', 'otp.verify', 'logout']);
    }

    /**
     * Get the active OTP for the given user
     *
     * @param App\User $user
     * @return \tpaksu\LaravelOTPLogin\OneTimePassword
     */
    private function getUserOTP($user)
    {
        return OneTimePassword::whereUserId($user->id)->where("status", "!=", "discarded")->first();
    }

    /**
     * Logs out the user with clearing the OTP records
     *
     * @param \tpaksu\LaravelOTPLogin\OneTimePassword $otp
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    private function logout($otp)
    {
        $otp->discardOldPasswords();
        \Auth::logout();
        return redirect('/');
    }

    /**
     * Checks if the cookie exists with an user id
     *
     * @return boolean
     */
    private function hasCookie()
    {
        return isset($_COOKIE["otp_login_verified"]) && starts_with($_COOKIE["otp_login_verified"], 'user_id_');
    }

    /**
     * Sets the cookie with the user ID inside, active for one year
     *
     * @param \App\User $user_id
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
}
