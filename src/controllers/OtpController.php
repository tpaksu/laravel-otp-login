<?php

namespace tpaksu\LaravelOTPLogin\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OtpController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */
    public function view(Request $request)
    {
        return view('laravel-otp-login::otpvalidate');
    }
	
	public function check(Request $request)
	{
		dd($request->input("code"));
	}
}