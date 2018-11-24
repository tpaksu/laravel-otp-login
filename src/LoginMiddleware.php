<?php

namespace tpaksu\LaravelOTPLogin;

use Closure;
use \Carbon\Carbon;

class LoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->bypassing() || in_array("auth", $request->route()->computedMiddleware) == false) {
            return $next($request);
        }

        $routeName = $request->route()->getName();
        if ($this->willCheck($routeName)) {
            $user = \Auth::user();
            $otp = $this->getUserOTP($user);
            $needsRefresh = false;
            if ($otp instanceof OneTimePassword) {
                if ($otp->status == "waiting") {
                    // check timeout
                    if ($otp->isExpired()) {
                        // expired. redirect to login page
                        $this->createCookie();
                        return $this->logout($otp);
                    } else {
                        // still valid. redirect to login verify screen
                        return redirect(route("otp.view"));
                    }
                } else if ($otp->status == "verified") {
                    // verified request. go forth.
                    $response = $next($request);
                    if ($response->status() == 419) {
                        // timeout occured
                        $this->createExpiredCookie();
                        return $this->logout($otp);
                    } else {
                        // will expire in one year
                        $this->createCookie($user->id);
                        return $response;
                    }
                } else {
                    // invalid status, needs to login again.
                    $this->createExpiredCookie();
                    return $this->logout($otp);
                }
            } else {
                // creating a new OTP login session
                $otp = OneTimePassword::create([
                    "user_id" => $user->id,
                    "status" => "waiting",
                ]);
                if ($otp->send() == true) {
                    return redirect(route('otp.view'));
                } else {
                    $this->createExpiredCookie();
                    return $this->logout($otp)->withError("message", "SMS service currently not responding.");
                }
            }
        } else {
            if (\Auth::guest() && $this->hasCookie()) {
                // expiration
                $user_id = $this->getUserIdFromCookie();
                OneTimePassword::whereUserId($user_id)->delete();
                $this->createExpiredCookie();
            }
        }
        return $next($request);
    }

    private function bypassing()
    {
        return \Session::has("otp_service_bypass") && \Session::get("otp_service_bypass", false);
    }

    private function willCheck($routeName)
    {
        return \Auth::check() && config("otp.otp_service_enabled", false) && !in_array($routeName, ['otp.view', 'otp.verify', 'logout']);
    }

    private function getUserOTP($user)
    {
        return OneTimePassword::whereUserId($user->id)->where("status", "!=", "discarded")->first();
    }

    private function logout($otp)
    {
        $otp->discardOldPasswords();
        \Auth::logout();
        return redirect(route('login'));
    }

    private function hasCookie()
    {
        return isset($_COOKIE["otp_login_verified"]) && starts_with($_COOKIE["otp_login_verified"], 'user_id_');
    }

    private function createCookie($user_id)
    {
        setcookie("otp_login_verified", "user_id_" . $user_id, time() + (365 * 24 * 60 * 60));
    }

    private function createExpiredCookie()
    {
        if ($this->hasCookie()) {
            setcookie("otp_login_verified", "", time() - 100);
        }
    }

    private function getUserIdFromCookie()
    {
        return intval(str_replace("user_id_", "", $_COOKIE["otp_login_verified"]));
    }
}
