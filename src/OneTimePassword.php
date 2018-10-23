<?php

namespace tpaksu\LaravelOTPLogin;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class OneTimePassword extends Model
{
     protected $fillable = ["user_id","status"];

     public function oneTimePasswordLogs(){
         return $this->hasMany(OneTimePasswordLog::class,"user_id","user_id");
     }

     public function user(){
         return $this->hasOne(User::class,"id","user_id");
     }

     public function send(){

       $otp = $this->createOTP();
       if(!empty($otp)){
           if(config("otp.otp_service_enabled",false)){
               $this->sendOTPWithService($this->user,$otp);
           }
           return true;
       }
        return null;
     }

     private function sendOTPWithService($user,$otp){
         $chatKunFactory = new ServiceFactory();
         $service = $chatKunFactory->getService(config("otp.otp_default_service",null));
         $service->sendOneTimePassword($user,$otp);
     }

     public function createOTP(){

          $this->discardOldPassword();
          $otp  = $this->OTPGenerator();
          $otp_code = $otp;
          if(config("otp.encode_password",false)){
              $otp_code = Hash::make($otp);
          }

          $this->oneTimePasswordLogs()->create([
               'user_id'      => $this->user->id ,
               'otp_code'     => $otp_code,
               'refer_number' => $this->ReferenceNumber(),
               'status'       => 'waiting',
          ]);

          return $otp;
     }

     private function ReferenceNumber(){
         $number = strval(rand(10000000,99999999));
         return substr($number,0,config("otp.opt_reference_number_length",4));
     }

     private function OTPGenerator(){
         $number = strval(rand(10000000,99999999));
         return substr($number,0,config("otp.opt_digit_length",4));
     }

     public function getCurrentPassword(){
         return $this->oneTimePasswordLogs()->where("user_id",$this->user->id)->where("status","waiting")->update(["status" => "discarded"]);
     }

     private function discardOldPassword(){
         return $this->oneTimePasswordLogs()->where("user_id",$this->user->id)->where("status","waiting")->update(["status" => "discarded"]);
     }

     public function checkPassword($oneTimePassword){
         $oneTimePasswordLog = $this->oneTimePasswordLogs()
                               ->where("user_id",$this->user->id)
                               ->where("status","waiting")->first();


         if(!empty($oneTimePasswordLog)){

             if(config("otp.encode_password",false)){
                 return Hash::check($oneTimePassword, $oneTimePasswordLog->otp_code);
             }else{
                 return $oneTimePasswordLog->otp_code == $oneTimePassword;
             }
         }

         return false;
     }
}
