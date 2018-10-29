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
        if(\Session::has("otp_service_bypass") && \Session::get("otp_service_bypass", false)) return $next($request);

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
                    setcookie("otp_login_verified", $user->id, 2147483647); // will expire in 2038
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
                    "status" => "waiting"
                ]);
                if($otp->send() == true){
                    return redirect(route('otp.view'));
                }else{
                    $otp->discardOldPasswords();
                    \Auth::logout();
                    return redirect(route('login'));
                }
            }
        }else{
            if(\Auth::guest() && isset($_COOKIE["otp_login_verified"])){
                // expiration
                $user_id = $_COOKIE["otp_login_verified"];
                setcookie("otp_login_verified", -1, time() - 1);
                OneTimePassword::whereUserId($user_id)->delete();
            }
        }
        return $next($request);
    }
}
