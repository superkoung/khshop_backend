<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Product_variant;
use App\Models\Size;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponse;
    public function index()
    {
        $categories=Category::latest()->get();

        return $this->successResponse(
            [
                'categories'=>$categories
            ],'Get categories data',200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData=$request->validate([
            'name'=>'required|string|max:255',
            'description'=>'sometimes|string',
            'image_path'=>'sometimes|required|image|mimes:png,jpg,webp|max:2048'
        ]);

        $validatedData['slug']=Str::slug($validatedData['name']);
        if(isset($validatedData['image_path'])){
            $validatedData['image_path'] =Storage::disk('public')->putFile('categories',$validatedData['image_path']);
        }
        $category=Category::create($validatedData);
        return $this->successResponse(
            [
                'category'=>$category
            ],'Category created successfully',201
        );

    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $category=Category::find($id);
        if(!$category){
            return $this->errorResponse('Category not found',404);
        }
        return $this->successResponse(
            [
                'category'=>$category
            ],'Get category data',200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $category=Category::find($id);
        if(!$category){
            return $this->errorResponse('Category not found',404);
        }

        $validatedData=$request->validate([
            'name'=>'sometimes|required|string|max:255',
            'description'=>'sometimes|string',
            'image_path'=>'sometimes|image|mimes:png,jpg,webp|max:2048',
            'is_active'=>'sometime|required|boolean'
        ]);

        if(isset($validatedData['name'])){
            $validatedData['slug']=Str::slug($validatedData['name']);
        }
        if(isset($validatedData['image_path'])){
            if($category->image_path && Storage::disk('public')->exists($category->image_path)){
                Storage::disk('public')->delete($category->image_path);
            }
            $validatedData['image_path'] =Storage::disk('public')->putFile('categories',$validatedData['image_path']);
        }
        $category->update($validatedData);
        return $this->successResponse(
            [
                'category'=>$category
            ],'Category updated successfully',200
        );

    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $category=Category::find($id);
        if(!$category){
            return $this->errorResponse('Category not found',404);
        }
        $category->delete();
        return $this->successResponse(null,'Category deleted successfully',200);
    }

    public function trashed(){
        $categories=Category::onlyTrashed()->paginate(10);

        return $this->successResponse(
            [
                'categories'=>$categories
            ],'Get deleted categories data',200
        );
    }

    public function restore(int $id){
        $category=Category::onlyTrashed()->find($id);
        if(!$category){
             return $this->errorResponse('Category not found',404);
        }
        $category->restore();
        return $this->successResponse(
            [
                'category'=>$category,
            ],'Category restored successfully',200
        );
    }

    public function forceDelete(int $id){
        $category=Category::onlyTrashed()->find($id);
        if(!$category){
             return $this->errorResponse('Category not found',404);
        }
        if($category->image_path && Storage::disk('public')->exists($category->image_path)){
                Storage::disk('public')->delete($category->image_path);
        }
        $category->forceDelete();
        return $this->successResponse(null,'Category permanently deleted',200);
    }
    // api front end
    public function getNavMenu()
    {
        $menus = Category::query()
            ->whereNull('parent_id')
            ->select(
                'id',
                'name',
                'slug'
            )
            ->with([
                // Menu Banner
                'banner' => function ($b) {
                    $b->select(
                        'id',
                        'menu_id',
                        'title',
                        'description',
                        'image_path'
                    );
                },

                // Child Categories
                'children' => function ($q) {
                    $q->select(
                        'id',
                        'name',
                        'slug',
                        'parent_id',
                        'image_path'
                    );
                },
            ])
            ->get();

        return $this->successResponse(
            [
                'menus' => $menus,
            ],
            'Get Navbar menu',
            200
        );
    }
}
