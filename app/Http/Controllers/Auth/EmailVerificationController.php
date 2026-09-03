<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\Otp;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    use ApiResponse;
    public function verifyEmail(VerifyOtpRequest $request){
        $validatedData=$request->validated();

        // Find otp by user
        $user=User::where('email',$validatedData['email'])->first();
        $otpRecord=Otp::where('user_id',$user->id)->where('type','email_verification')->latest()->first();

        // check correctly otp
        if(!$otpRecord || $otpRecord->otp !== $validatedData['otp']){
            return $this->errorResponse('Invalid Otp number',422);
        }

        // check expire otp
        if(Carbon::now()->greaterThan($otpRecord->created_at->addMinutes(5))){
            return $this->errorResponse('This OTP number has expired! Please click Resend OTP again.',422);
        }

        // set active to email of user
        $user->update([
            'is_active'=>true,
            'email_verified_at'=>now()
        ]);

        // delete otp
        $otpRecord->delete();

        // create token to user
        $token=$user->createToken('auth_token')->plainTextToken;

        return $this->successResponse(
            [
                'user'=>$user,
                'token'=>$token,
                'type'=>'bearer'
            ],'Email verification successful!',200
        );
    }

    public function resendOtp(Request $request){
        $validatedData=$request->validate([
            'email'=>'required|email|max:255|exists:users,email'
        ]);
        $user=User::where('email',$validatedData['email'])->first();

        $otp=rand(111111,999999);
        $otpRecord=Otp::where('user_id',$user->id)->where('type','email_verification')->latest()->first();

        if(now()->diffInMinutes($otpRecord->expires_at,false)>2){
            return $this->errorResponse('Please wait 2 minute before requesting a new OTP number.',429);
        }
        Otp::where('user_id', $user->id)->where('type', 'email_verification')->delete();

        Otp::create([
            'user_id'=>$user->id,
            'otp'=>$otp,
            'type'=>'email_verification',
            'expires_at'=>now()->addMinutes(5)
        ]);
        $data=[
            'otp'=>$otp
        ];
        Mail::send('emails.otp',$data,function($message) use ($user){
            $message->to($user->email)->subject('New OTP number sent to your email!');
        });
        return $this->successResponse(null,'New OTP number sent to your email',200);
    }
}
