<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users=User::with('role')->where('is_active',true)->paginate(10);
        return $this->successResponse(
            [
                'users'=>$users
            ],
            "Get users data successfully",200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $validatedData=$request->validated();

        $user=User::create([
            'name'=>$validatedData['name'],
            'email'=>$validatedData['email'],
            'password'=>Hash::make($validatedData['password']),
            'role_id'=>$validatedData['role_id'],
            'is_active'=>true
        ]);
        return $this->successResponse(
            [
                'user'=>$user
            ],'User created successfully',201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user=User::with('role')->where('is_active',true)->where('id',$id)->first();
        if(!$user){
            return $this->errorResponse('User not found',404);
        }
        return $this->successResponse(
            [
                'user'=>$user
            ],'User found!',200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, string $id)
    {
        $validatedData=$request->validated();

        $user=User::with('role')->where('is_active',true)->find($id);
        if(!$user){
            return $this->errorResponse('User not found',404);
        }

        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }
        $user->update($validatedData);

        $user->load('role');

        return $this->successResponse(
            [
                'user'=>$user
            ],'User updated successfully',200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user=User::where('is_active',true)->find($id);
        if(!$user){
            return $this->errorResponse('User not found',404);
        }
        if ($request->user()->id === $user->id) {
            return $this->errorResponse('You are not allowed to delete your own account.', 403);
        }
        $user->tokens()->delete();
        $user->update([
            'is_active'=>false
        ]);
        $user->delete();
        return $this->successResponse(null,'User account deleted successfully');
    }
}
