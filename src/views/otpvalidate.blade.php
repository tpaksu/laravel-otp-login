@extends('layouts.login')

@section('content')
<div class="container">
    <div class="row">
        <div class="col col-login mx-auto">
            <div class="text-center mb-6">
                <img src="{{asset('assets/images/provas_male.png')}}" class="h-9" alt="">
            </div>
            <form method="POST" action="{{ route('otp.verify') }}" class="card" aria-label="{{ __('Verify') }}">
                @csrf
                <div class="card-body p-6">
                    <div class="card-title">{{__("Verify Your Phone number")}}</div>
					<p>{{__("Thanks for giving your details. An OTP has been sent to your Mobile Number. Please enter the :digit digit OTP below for Successful Login", ["digit" => config("otp.otp_digit_length", 6)])}}</p>
                    <div class="form-group">
                        <label class="form-label">{{__("One-Time Password")}}</label>
                        <input id="code" type="text" class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}" name="code" value="{{ old('code') }}"
                            required autofocus> @if ($errors->has('code'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('code') }}</strong>
                        </span>
                        @endif
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary btn-block">{{__("Verify Your Phone Number")}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
