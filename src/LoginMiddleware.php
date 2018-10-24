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
        if (\Auth::check()) {
            $user = \Auth::user();
            $otp = OneTimePassword::whereUserId($user->id);
            $needsRefresh = false;
            if ($otp->count() > 0) {
                $otp = $otp->orderByDesc("created_at")->first();
                if ($otp->status == "waiting") {
                    // check timeout
                    if($otp->status < Carbon::now()->subSeconds(config("otp.otp_timeout"))){
                        // expired.
                        OneTimePassword::whereUserId($user->id)->delete();
                        // needs a new verification
                        $needsRefresh = true;
                    }else{
                        // still valid. redirect to login verify screen
                        return redirect(route("otp.login"));
                    }
                } else if ($otp->status == "verified") {
                    // verified request. go forth.
                    return $next($request);
                } else {
                    // invalid status
                    OneTimePassword::whereUserId($user->id)->delete();
                    // needs a new verification
                    $needsRefresh = true;
                }
            } else {
                // verification doesn't exist
                OneTimePassword::whereUserId($user->id)->delete();
                // needs a new verification
                $needsRefresh = true;
            }

            if($needsRefresh){
                $otp = OneTimePassword::create([
                    "user_id" => $user->id,
                    "status" => "waiting"
                ]);
                $otp->send();
                return redirect(route('otp.login'));
            }
        }
        return $next($request);
    }
}
