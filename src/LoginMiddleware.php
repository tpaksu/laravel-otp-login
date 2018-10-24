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
		$routeName = $request->route()->getName();
        if (\Auth::check() && !in_array($routeName, ['otp.view', 'otp.verify', 'logout'])) {
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
                        // needs to login again.
						\Auth::logout();                        
                    }else{
                        // still valid. redirect to login verify screen
                        return redirect(route("otp.view"));
                    }
                } else if ($otp->status == "verified") {
                    // verified request. go forth.
                    return $next($request);
                } else {
                    // invalid status
                    OneTimePassword::whereUserId($user->id)->delete();
                    // needs to login again.
					\Auth::logout();      
                }
            } else {               
                // needs a new verification
                $needsRefresh = true;
            }

            if($needsRefresh){
                $otp = OneTimePassword::create([
                    "user_id" => $user->id,
                    "status" => "waiting"
                ]);
                $otp->send();
                return redirect(route('otp.view'));
            }
        }else if(\Auth::check() && $routeName == "logout"){			
			OneTimePassword::whereUserId(\Auth::user()->id)->delete();
		}
        return $next($request);
    }
}
