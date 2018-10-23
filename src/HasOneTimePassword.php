<?php


namespace tpaksu\LaravelOTPLogin;


trait HasOneTimePassword
{
    private function initOTP($opt)
    {
        $opt->create([
            'user_id' => $this->id,
            'status' => 'clean-sheet'
        ]);
    }

    public function OTP()
    {
        $opt = $this->hasOne(OneTimePassword::class, "user_id", "id");

        if ($opt->count() == 0) {
            $this->initOTP($opt);
            $opt = $this->hasOne(OneTimePassword::class, "user_id", "id");
        }
        return $opt;
    }
}
