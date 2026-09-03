<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\Otp;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    use ApiResponse;
    public function forgotPassword(Request $request){
        $validatedData=$request->validate([
            'email'=>'required|email|max:255'
        ]);
        $user=User::where('email',$validatedData['email'])->first();
        if(!$user){
            return $this->errorResponse('No account exists with this email',404);
        }
        $otp=rand(111111,999999);
        Otp::create([
            'user_id'=>$user->id,
            'otp'=>$otp,
            'type'=>'reset_password',
            'expires_at'=>now()->addMinutes(5)
        ]);
        $data=[
            'otp'=>$otp
        ];
        Mail::send('emails.otp',$data,function($message) use($user){
            $message->to($user->email)->subject('OTP number to reset password!');
        });
        return $this->successResponse(null,'OTP number send to your email!',200);
    }

    public function verifyOtp(VerifyOtpRequest $request){
        $validatedData=$request->validated();
        $user=User::where('email',$validatedData['email'])->first();
        $otpRecord=Otp::where('user_id',$user->id)->where('type', 'reset_password')->latest()->first();

        if(!$otpRecord || $otpRecord->otp !== $validatedData['otp']){
            return $this->errorResponse('Invalid OTP number!',422);
        }
        if(Carbon::now()->greaterThan($otpRecord->expires_at)){
            return $this->errorResponse('This OTP number has expired! Please click Resend OTP again.',422);
        }
        return $this->successResponse(null,'The OTP number is correct! You can proceed to change your new password.',200);

    }

    public function resetPassword(ResetPasswordRequest $request){
        $validatedData=$request->validated();
        $user=User::where('email',$validatedData['email'])->first();
        $otpRecord=Otp::where('user_id',$user->id)->where('type','reset_password')->latest()->first();

        if(Carbon::now()->greaterThan($otpRecord->expires_at) || $otpRecord->otp !== $validatedData['otp']){
            return $this->errorResponse('Reset password has expired! Please click Resend OTP again',422);
        }
        $user->update([
            'password'=>Hash::make($validatedData['password'])
        ]);
        $otpRecord->delete();

        $user->tokens()->delete();

        return $this->successResponse(null,'Reset Password Successfully!',201);

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
        Otp::where('user_id', $user->id)->where('type', 'reset_password')->delete();

        Otp::create([
            'user_id'=>$user->id,
            'otp'=>$otp,
            'type'=>'reset_password',
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
