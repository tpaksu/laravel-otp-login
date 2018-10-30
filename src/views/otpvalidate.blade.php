@extends('layouts.login')

@section('content')
<div class="container">
    <div class="row">
        <div class="col col-login mx-auto">
            <div class="text-center mb-6">
                <img src="{{asset('assets/images/provas_male.png')}}" class="h-9" alt="">
            </div>
            <form method="POST" action="{{ route('otp.verify') }}" class="card" aria-label="{{ __('laravel-otp-login::messages.verify_phone_button') }}">
                @csrf
                <div class="card-body p-6">
                    <div class="card-title">{{__("laravel-otp-login::messages.verify_phone_title")}}</div>
					<p>{{__("laravel-otp-login::messages.hero_text", ["digit" => config("otp.otp_digit_length", 6)])}}</p>
                    <div class="form-group">
                        <label class="form-label">{{__("laravel-otp-login::messages.one_time_password")}}</label>
                        <input id="code" type="text" class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}" name="code" value="{{ old('code') }}"
                            required autofocus autocomplete="off"> @if ($errors->has('code'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('code') }}</strong>
                        </span>
                        @endif
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary btn-block">{{__("laravel-otp-login::messages.verify_phone_button")}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
