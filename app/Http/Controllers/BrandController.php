<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class BrandController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Brand::latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $brands=$query->paginate($request->integer('per_page', 10));

        return $this->successResponse(
            ['brands'=>$brands],
            'Get brands data',
            200
        );
    }

    public function store(Request $request)
    {
        $validatedData=$request->validate([
            'name'=>'required|string|max:255',
            'image_path'=>'sometimes|image|mimes:png,jpg,webp|max:2048',
        ]);
        $validatedData['slug']=Str::slug($validatedData['name']);
        if(isset($validatedData['image_path'])){
            $uploaded = $request->file('image_path')->storeOnCloudinary('brands');
            $validatedData['image_path'] = $uploaded->getSecurePath();
            $validatedData['public_id'] = $uploaded->getPublicId();
        }
        $brand=Brand::create($validatedData);
        return $this->successResponse(
            ['brand'=>$brand],
            'Brand created successfully',
            201
        );
    }

    public function show(Brand $brand)
    {
        return $this->successResponse(
            ['brand'=> new BrandResource($brand)],
            'Get brand data',
            200
        );
    }

    public function update(Request $request, Brand $brand)
    {
        $validatedData=$request->validate([
            'name'=>'sometimes|required|string|max:255',
            'image_path'=>'sometimes|image|mimes:png,jpg,webp|max:2048',
            'is_active'=>'sometimes|required|boolean'
        ]);
        if(isset($validatedData['name'])){
            $validatedData['slug']=Str::slug($validatedData['name']);
        }
        if(isset($validatedData['image_path'])){
            if(!empty($brand->public_id)){
                Cloudinary::destroy($brand->public_id);
            }
            $uploaded = $request->file('image_path')->storeOnCloudinary('brands');
            $validatedData['image_path'] = $uploaded->getSecurePath();
            $validatedData['public_id'] = $uploaded->getPublicId();
        }
        $brand->update($validatedData);
        return $this->successResponse(
           ['brand'=>new BrandResource($brand)],
           'Brand updated successfully',
           200
        );
    }

    public function destroy(Brand $brand)
    {
        if(!empty($brand->public_id)){
            Cloudinary::destroy($brand->public_id);
        }
        $brand->delete();
        return $this->successResponse(null,'Brand deleted successfully',200);
    }

    public function forceDelete(int $id){
        $brand=Brand::onlyTrashed()->findOrFail($id);
        if(!empty($brand->public_id)){
            Cloudinary::destroy($brand->public_id);
        }
        $brand->forceDelete();
        return $this->successResponse(null,'Brand permanently deleted',200);
    }

    public function trashed(){
        $brand=Brand::onlyTrashed()->paginate(10);
        return $this->successResponse(
            ['brands'=>$brand],
            'Get deleted brand data',
            200
        );
    }

    public function restore(int $id){
        $brand=Brand::onlyTrashed()->findOrFail($id);
        $brand->restore();
        return $this->successResponse(
            ['brand'=>$brand],
            'Brand restored successfully',
            200
        );
    }
}
