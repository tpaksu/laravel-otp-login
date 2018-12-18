@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{__("laravel-otp-login::messages.verify_phone_title")}}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('otp.verify') }}">
                        @csrf

                        <p>{{__("laravel-otp-login::messages.hero_text", ["digit" => config("otp.otp_digit_length", 6)])}}</p>

                        <div class="form-group">
                            <label class="form-label">{{__("laravel-otp-login::messages.one_time_password")}}</label>

                            <input id="code" type="text" class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}" name="code" value="{{ old('code') }}"
                            required autofocus autocomplete="off">
                            @if ($errors->has('code'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('code') }}</strong>
                            </span>
                            @endif
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">{{__("laravel-otp-login::messages.verify_phone_button")}}</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4">
                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    {{ __('laravel-otp-login::messages.otp_not_received') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
