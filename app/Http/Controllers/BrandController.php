<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands=Brand::latest()->paginate(10);

        return $this->successResponse(
            ['brands'=>BrandResource::collection($brands)],
            'Get brands data',
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData=$request->validate([
            'name'=>'required|string|max:255',
            'image_path'=>'sometimes|image|mimes:png,jpg,webp|max:2048',
        ]);
        $validatedData['slug']=Str::slug($validatedData['name']);
        if(isset($validatedData['image_path'])){
            $validatedData['image_path']=Storage::disk('public')->putFile('brands',$validatedData['image_path']);
        }
        $brand=Brand::create($validatedData);
        return $this->successResponse(
            ['brand'=>$brand],
            'Brand created successfully',
            201
        );

    }
    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return $this->successResponse(
            ['brand'=> new BrandResource($brand)],
            'Get brand data',
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validatedData=$request->validate([
            'name'=>'sometimes|required|string|max:255',
            'image_path'=>'sometimes|image|mimes:png,jpg,webp|max:2048',
            'is_active'=>'sometimes|required|boolean'
        ]);
        if(isset($validatedData['image_path'])){
            if($brand->image_path && Storage::disk('public')->exists($brand->image_path)){
                Storage::disk('public')->delete($brand->image_path);
            }
        }
        if(isset($validatedData['name'])){
            $validatedData['slug']=Str::slug($validatedData['name']);
        }
       $brand->update($validatedData);
       return $this->successResponse(
          ['brand'=>new BrandResource($brand)],
          'Brand updated successfully',
          200
       );

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $brand->delete();

        return $this->successResponse(null,'Brand deleted successfully',200);
    }
    public function forceDelete(int $id){
        $brand=Brand::onlyTrashed()->findOrFail($id);
        if($brand->image_path && Storage::disk('public')->exists($brand->image_path)){
            Storage::disk('public')->delete($brand->image_path);
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
