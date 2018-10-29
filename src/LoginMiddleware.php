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
        \Debugbar::log($_COOKIE);
        if (\Session::has("otp_service_bypass") && \Session::get("otp_service_bypass", false)) {
            return $next($request);
        }

        $routeName = $request->route()->getName();
        if (\Auth::check() && config("otp.otp_service_enabled", false) && !in_array($routeName, ['otp.view', 'otp.verify', 'logout'])) {
            $user = \Auth::user();
            $otp = OneTimePassword::whereUserId($user->id)->where("status", "<>", "discarded");
            $needsRefresh = false;
            if ($otp->count() > 0) {
                $otp = $otp->orderByDesc("created_at")->first();
                if ($otp->status == "waiting") {
                    // check timeout
                    if ($otp->status < Carbon::now()->subSeconds(config("otp.otp_timeout"))) {
                        // expired.
                        $otp->discardOldPasswords();
                        // needs to login again.
                        \Auth::logout();
                    } else {
                        // still valid. redirect to login verify screen
                        return redirect(route("otp.view"));
                    }
                } else if ($otp->status == "verified") {
                    // will expire in one year
                    setcookie("otp_login_verified", "user_id_" . $user->id, time() + (365 * 24 * 60 * 60));
                    // verified request. go forth.
                    return $next($request);
                } else {
                    // invalid status
                    // needs to login again.
                    \Auth::logout();
                    return redirect("/");
                }
            } else {
                // needs a new verification
                $needsRefresh = true;
            }

            if ($needsRefresh) {
                $otp = OneTimePassword::create([
                    "user_id" => $user->id,
                    "status" => "waiting",
                ]);
                if ($otp->send() == true) {
                    return redirect(route('otp.view'));
                } else {
                    $otp->discardOldPasswords();
                    \Auth::logout();
                    return redirect(route('login'));
                }
            }
        } else {
            if (\Auth::check() == false && isset($_COOKIE["otp_login_verified"]) !== false) {
                // expiration
                $user_id = intval(str_replace("user_id_", "", \Cookie::get("otp_login_verified")));
                setcookie("otp_login_verified", "", time() - 3600);
                unset($_COOKIE['otp_login_verified']);
                OneTimePassword::whereUserId($user_id)->delete();
            }
        }
        return $next($request);
    }
}
