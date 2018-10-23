<?php

namespace tpaksu\LaravelOTPLogin;

use Illuminate\Database\Eloquent\Model;

class OneTimePasswordLog extends Model
{
    protected $fillable = ["user_id", "otp_code", "status", "refer_number"];
}
