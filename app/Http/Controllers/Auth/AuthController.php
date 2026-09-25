<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Otp;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewCustomerNotification;
use App\Services\NotificationDispatcher;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

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

            // Only after successful customer account creation.
            NotificationDispatcher::toAdminStaff(new NewCustomerNotification(
                (int) $user->id,
                $user->name
            ));

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
    // public function login(LoginRequest $request){
    //     $validatedData=$request->validated();

    //     try{
    //         $user=User::where('email',$validatedData['email'])->first();
    //         if(!$user || !Hash::check($validatedData['password'],$user->password)){
    //             return $this->errorResponse('Invalid email or password',401);
    //         }
    //         if(!$user->is_active){
    //             return $this->errorResponse('Your account is not active or verified yet',403);
    //         }
    //         $token=$user->createToken('auth_token')->plainTextToken;

    //         return $this->successResponse(
    //             [
    //                 'token'=>$token,
    //                 'token_type'=>'bearer'
    //             ],'Login successfully!',200
    //         );

    //     }catch(\Exception $e){
    //         return $this->errorResponse('Login failed',500,$e->getMessage());
    //     }

    // }

    /**
     * Shared customer + admin/staff login with failed-attempt rate limiting.
     * Max consecutive failures (config auth.login.max_attempts, default 5)
     * then HTTP 429 for the cooldown (config auth.login.decay_minutes, default 15).
     * Key: login|{normalized email}|{client IP} — independent per account/IP.
     */
    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();
        $throttleKey = $this->loginThrottleKey($request, $validatedData['email']);
        $maxAttempts = max(1, (int) config('auth.login.max_attempts', 5));
        $decaySeconds = max(60, (int) config('auth.login.decay_minutes', 15) * 60);

        // Cooldown active — do not attempt authentication.
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            return $this->tooManyLoginAttemptsResponse($throttleKey);
        }

        try {
            $user = User::with('role')
                ->where('email', $validatedData['email'])
                ->first();

            if (!$user || !Hash::check($validatedData['password'], $user->password)) {
                RateLimiter::hit($throttleKey, $decaySeconds);

                // Attempt #maxAttempts triggers rate limit immediately.
                if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
                    return $this->tooManyLoginAttemptsResponse($throttleKey);
                }

                return $this->errorResponse(
                    'Invalid email or password',
                    401
                );
            }

            if (!$user->is_active) {
                return $this->errorResponse(
                    'Your account is not active or verified yet',
                    403
                );
            }

            RateLimiter::clear($throttleKey);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(
                [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role->name,
                    ],
                ],
                'Login successfully!',
                200
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Login failed',
                500,
                $e->getMessage()
            );
        }
    }

    private function loginThrottleKey(Request $request, string $email): string
    {
        return sprintf('login|%s|%s', strtolower(trim($email)), $request->ip());
    }

    private function tooManyLoginAttemptsResponse(string $throttleKey)
    {
        $retryAfter = max(1, RateLimiter::availableIn($throttleKey));

        return response()->json([
            'status' => false,
            'message' => 'Too many failed login attempts. Please try again later.',
            'errors' => null,
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null,'Logout Successfully!',200);
    }
}
