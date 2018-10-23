<?php

namespace tpaksu\LaravelOTPLogin;

use Closure;

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
        if(\Auth::check() && $request->session()->has("otp_seed") == false && $request->route()->uri != "login/otp/check"){
            return redirect(route('otp.login'));
        }
        \Debugbar::log($request->route());
        return $next($request);
    }
}
