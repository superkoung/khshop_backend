<?php

namespace App\Http\Controllers;

use App\Http\Requests\updateProfileRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request){
        $user = $request->user();

        return $this->successResponse($user, 'profile user', 200);
    }

    public function update(updateProfileRequest $request){
        $user = $request->user();
        $user->update($request->validated());

        return $this->successResponse($user, 'update profile successfully', 200);
    }

    public function delete(Request $request){
        $validateData = $request->validate([
            'password' => 'required|string'
        ]);

        $user = $request->user();

        if (! Hash::check($validateData['password'], $user->password)) {
            return $this->errorResponse("Current password is incorrect.", 422);
        }

        // delete token
        $user->tokens()->delete();

        // delete old avatar on Cloudinary if exists
        if ($user->avatar_public_id) {
            Cloudinary::destroy($user->avatar_public_id);
        }

        // delete account
        $user->delete();

        return $this->successResponse(null, 'Account deleted successfully', 200);
    }

    public function updateAvatar(Request $request){
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $user = $request->user();

        // ១. លុបរូបចាស់នៅលើ Cloudinary (បើមាន public_id ចាស់)
        if ($user->public_id) {
            Cloudinary::destroy($user->public_id);
        }

        // ២. Upload រូបថ្មីទៅកាន់ Cloudinary
        $uploadedFile = $request->file('avatar')->storeOnCloudinary('profile_images');

        // ៣. រក្សាទុក Secure URL និង Public ID ក្នុង Database
        $user->avatar_path = $uploadedFile->getSecurePath();
        $user->public_id = $uploadedFile->getPublicId(); // គួរមាន column នេះដើម្បីស្រួលលុបថ្ងៃក្រោយ
        $user->save();

        return $this->successResponse(['avatar' => $user->avatar_path], 'Avatar updated successfully', 200);
    }

    public function deleteAvatar(Request $request){
            $user = $request->user();

            // ១. លុបរូបភាពពី Cloudinary
            if ($user->public_id) {
                Cloudinary::destroy($user->public_id);
            }

            // ២. កំណត់តម្លៃទៅជា null វិញក្នុង Database
            $user->avatar_path = null;
            $user->public_id = null;
            $user->save();

            return $this->successResponse(null, 'Avatar deleted successfully', 200);
    }
}
