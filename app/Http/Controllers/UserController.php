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

    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->input('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->successResponse(
            ['users' => $users],
            'Users retrieved successfully',
            200
        );
    }

    public function store(UserStoreRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'] ?? null,
            'password' => Hash::make($validatedData['password']),
            'role_id' => $validatedData['role_id'],
            'is_active' => true,
        ]);

        $user->load('role');

        return $this->successResponse(
            ['user' => $user],
            'User created successfully',
            201
        );
    }

    public function show(string $id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        return $this->successResponse(
            ['user' => $user],
            'User found',
            200
        );
    }

    public function update(UserUpdateRequest $request, string $id)
    {
        $validatedData = $request->validated();

        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);
        $user->load('role');

        return $this->successResponse(
            ['user' => $user],
            'User updated successfully',
            200
        );
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        if ($request->user()->id === $user->id) {
            return $this->errorResponse('You are not allowed to delete your own account.', 403);
        }

        $user->tokens()->delete();
        $user->update(['is_active' => false]);
        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }
}
