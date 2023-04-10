<?php

namespace tpaksu\LaravelOTPLogin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OneTimePassword extends Model
{
    protected $fillable = ["user_id", "status"];

    public function oneTimePasswordLogs()
    {
        return $this->hasMany(OneTimePasswordLog::class, "user_id", "user_id");
    }

    public function user()
    {
        return $this->hasOne(
            config("otp.user_class", config("auth.providers.users.model")),
            config("otp.user_id_field", "id"),
            "user_id"
        );
    }

    public function send()
    {
        $referenceNumber = $this->generateReferenceNumber();

        $otp = $this->createOTP($referenceNumber);
        if (!empty($otp)) {
            if (config("otp.otp_service_enabled", false)) {
                return $this->sendOTPWithService($this->user, $otp, $referenceNumber);
            }
            return true;
        }

        return null;
    }

    private function sendOTPWithService($user, $otp, $ref)
    {
        $OTPFactory = new ServiceFactory();
        $service = $OTPFactory->getService(config("otp.otp_default_service", null));
        if ($service) {
            return $service->sendOneTimePassword($user, $otp, $ref);
        }
        return false;
    }

    public function createOTP($referenceNumber)
    {
        $this->discardOldPasswords();
        $otp = $this->generateOTP();

        $otpCode = $otp;

        if (config("otp.encode_password", false)) {
            $otpCode = Hash::make($otp);
        }
        $this->update(["status" => "waiting"]);

        $this->oneTimePasswordLogs()->create([
            'user_id' => $this->user->{config("otp.user_id_field", "id")},
            'otp_code' => $otpCode,
            'refer_number' => $referenceNumber,
            'status' => 'waiting',
        ]);

        return $otp;
    }

    private function generateReferenceNumber()
    {
        $digits = config("otp.otp_reference_number_length", 4);
        return $this->generateNumberWithDigits($digits);
    }

    private function generateOTP()
    {
        $digits = config("otp.otp_digit_length", 4);
        return $this->generateNumberWithDigits($digits);
    }

    private function generateNumberWithDigits($digits)
    {
        $number = strval(rand((int) pow(10, $digits - 1), (int) pow(10, $digits) - 1));
        return substr($number, 0, $digits);
    }

    public function discardOldPasswords()
    {
        $this->update(["status" => "discarded"]);
        return $this->oneTimePasswordLogs()
            ->whereIn("status", ["waiting", "verified"])
            ->update(["status" => "discarded"]);
    }

    public function checkPassword($oneTimePassword)
    {
        $oneTimePasswordLog = $this->oneTimePasswordLogs()
            ->where("status", "waiting")->first();

        if (!empty($oneTimePasswordLog)) {
            if (config("otp.encode_password", false)) {
                return Hash::check($oneTimePassword, $oneTimePasswordLog->otp_code);
            } else {
                return $oneTimePasswordLog->otp_code == $oneTimePassword;
            }
        }

        return false;
    }

    public function acceptEntrance()
    {
        $this->update(["status" => "verified"]);
        $this->oneTimePasswordLogs()->where("status", "discarded")->delete();
        OneTimePassword::where([
            "status" => "discarded",
            "user_id" => $this->user->getAttribute(
                config("otp.user_id_field", "id")
            )])->delete();
        return $this->oneTimePasswordLogs()
            ->where("user_id", $this->user->getAttribute(config("otp.user_id_field", "id")))->where("status", "waiting")
            ->update(["status" => "verified"]);
    }

    public function isExpired()
    {
        return $this->created_at < Carbon::now()->subSeconds(config("otp.otp_timeout", 300));
    }
}
