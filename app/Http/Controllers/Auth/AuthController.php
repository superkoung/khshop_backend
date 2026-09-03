<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Otp;
use App\Models\Role;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request){

        $validatedData=$request->validated();
        DB::beginTransaction();
        try{
            $role=Role::firstOrCreate([
                'name'=>'user'
            ]);

            $user=User::create([
                'role_id'=>$role->id,
                'name'=>$validatedData['name'],
                'email'=>$validatedData['email'],
                'password'=>Hash::make($validatedData['password']),
                'is_active'=>false
            ]);

            // send opt
            $otp=rand(100000,999999);
            Otp::create([
                'user_id'=>$user->id,
                'otp'=>$otp,
                'type' => 'email_verification',
                'expires_at' => now()->addMinutes(5)
            ]);
            $data = [
                'otp' => $otp
            ];
            Mail::send('emails.otp', $data, function ($message) use ($user) {
                $message->to($user->email)->subject('OTP number to verify your email');
            });
            DB::commit();

            return $this->successResponse([
                'user'=>$user,
            ],
            'Register successfully',
            201);
        }catch(\Exception $e){
            DB::rollBack();
            return $this->errorResponse('Register failed',500,$e->getMessage());
        }

    }
    public function login(LoginRequest $request){
        $validatedData=$request->validated();

        try{
            $user=User::where('email',$validatedData['email'])->first();
            if(!$user || !Hash::check($validatedData['password'],$user->password)){
                return $this->errorResponse('Invalid email or password',401);
            }
            if(!$user->is_active){
                return $this->errorResponse('Your account is not active or verified yet',403);
            }
            $token=$user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(
                [
                    'token'=>$token,
                    'token_type'=>'bearer'
                ],'Login successfully!',200
            );

        }catch(\Exception $e){
            return $this->errorResponse('Login failed',500,$e->getMessage());
        }

    }
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null,'Logout Successfully!',200);
    }
}
