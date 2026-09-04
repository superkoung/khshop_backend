<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ImageController extends Controller
{
    use ApiResponse;

    public function index(String $variant_id){
        $images = Image::where('variant_id', $variant_id)->get();

        return $this->successResponse(
            ['images' => $images],
            'Get image product variants',
            200
        );
    }

    public function store(Request $request){
        $validatedData = $request->validate([
            'variant_id' => 'required',
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:png,jpg,webp,jpeg|max:2048',
            'primary_image' => 'required|image|mimes:png,jpg,webp,jpeg|max:2048'
        ]);

        $createdImages = [];

        // 1. Upload Primary Image to Cloudinary
        $primaryUpload = $request->file('primary_image')->storeOnCloudinary('products');

        $createdImages[] = Image::create([
            'variant_id' => $validatedData['variant_id'],
            'image_path' => $primaryUpload->getSecurePath(),
            'public_id'  => $primaryUpload->getPublicId(), // រក្សាទុក Public ID សម្រាប់លុបថ្ងៃក្រោយ
            'is_primary' => true
        ]);

        // 2. Upload Gallery Images to Cloudinary
        foreach ($request->file('images') as $img) {
            $uploadedImg = $img->storeOnCloudinary('products');

            $createdImages[] = Image::create([
                'variant_id' => $validatedData['variant_id'],
                'image_path' => $uploadedImg->getSecurePath(),
                'public_id'  => $uploadedImg->getPublicId(),
                'is_primary' => false
            ]);
        }

        return $this->successResponse(
            ['images' => $createdImages],
            'Images created successfully',
            201
        );
    }

    public function destroy(String $id){
        $image = Image::findOrFail($id);

        // លុបរូបភាពចេញពី Cloudinary ដោយប្រើ public_id
        if (!empty($image->public_id)) {
            Cloudinary::destroy($image->public_id);
        }

        $image->delete();

        return $this->successResponse(null, "Image deleted successfully", 200);
    }
}
