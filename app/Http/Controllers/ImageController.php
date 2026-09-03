<?php

namespace App\Http\Controllers;
use App\Models\Image;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    use ApiResponse;
    public function index(String $variant_id){
        $images=Image::where('variant_id',$variant_id);
        return $this->successResponse(
            ['images'=>$images],
            'Get image product variants',
            200
        );
    }

    public function store(Request $request){
        $validatedData=$request->validate([
            'variant_id'=>'required',
            'images'=>'required|array',
            'images.*'=>'required|image|mimes:png,jpg,webp,jpeg|max:2048',
            'primary_image'=>'required|image|mimes:png,jpg,webp,jpeg|max:2048'
        ]);
        $images=[];
        //primary image
        $images[]=Image::create([
            'variant_id'=>$validatedData['variant_id'],
            'image_path'=>$validatedData['primary_image'],
            'is_primary'=>true
        ]);
        Storage::disk('public')->putFile('products',$validatedData['primary_image']);
        // simple images
        foreach ($validatedData['images'] as $img){
            $images[]=Image::create([
                'variant_id'=>$validatedData['variant_id'],
                'image_path'=>$img,
                'is_primary'=>false
            ]);

            Storage::disk('public')->putFile('products',$img);
        }

        return $this->successResponse(
            ['images'=>$images],
            'Image created successfully',
            201
        );
    }

    public function destroy(String $id){
        $image=Image::query()->findOrFail($id);

        if(Storage::disk('public')->exists($image->image_path)){
            Storage::disk('public')->delete($image->image_path);
        }
        $image->delete();
        return $this->successResponse(null, "Image deleted successfully",200);
    }
}
