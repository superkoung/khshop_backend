<?php

namespace App\Http\Controllers;

use App\Models\Product_variant;
use App\Models\Image;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class Product_variantController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products=Product_variant::query()->latest()->paginate(10);
        return $this->successResponse(
            ['products'=>$products],
            'Get all product data',
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData=$request->validate([
            'product_id'=>'required|exists:products,id',
            'size_id'=>'sometimes|required|exists:sizes,id',
            'color_id'=>'sometimes|required|exists:colors,id',
            'stock'=>'required|integer|min:0',
            'price_modifier'=>'sometimes|numeric|min:0',
            'is_active'=>'boolean|sometimes',
            'sku'=>'required|string|max:255|unique:product_variants,sku',

        ]);

        $variant = Product_variant::create([
            'product_id' => $validatedData['product_id'],
            'size_id' => $validatedData['size_id'],
            'color_id' => $validatedData['color_id'],
            'sku' => $validatedData['sku'],
            'price_modifier' => $validatedData['price_modifier']?? 0,
            'stock' => $validatedData['stock'],
            'is_active'=> $validatedData['is_active']?? true
        ]);

        return $this->successResponse(
            ['variant'=>$variant],
            'Product variant created successfully',
            201
        );

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $variant=Product_variant::query()->findOrFail($id);
        return $this->successResponse(
            ['variant'=>$variant],
            'Get product variant data',
            200
        );
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $variant=Product_variant::query()->findOrFail($id);

        $validatedData=$request->validate([
            'product_id'=>'sometimes|required|exists:products,id',
            'size_id'=>'sometimes|required|exists:sizes,id',
            'color_id'=>'sometimes|required|exists:colors,id',
            'stock'=>'sometimes|required|integer|min:0',
            'price_modifier'=>'sometimes|numeric|min:0',
            'is_active'=>'sometimes|boolean',
            Rule::unique('product_variants', 'sku')->ignore($variant->id),
        ]);
        $variant->update($validatedData);
        return $this->successResponse(
            ['variant'=>$variant],
            'Product variant updated successfully',
            200
        );
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Product_variant::query()->findOrFail($id)->delete();
        return $this->successResponse(null, 'Product variant deleted successfully',200);
    }

    public function forceDelete(int $id){
        Product_variant::onlyTrashed()->findOrFail($id)->forceDelete();
        return $this->successResponse(null,'Product variant permanently deleted',200);
    }
    public function trashed(){
        $variants=Product_variant::onlyTrashed()->latest()->paginate(10);
        return $this->successResponse(
            ['variants'=>$variants],
            'Get all product variant trashed',200
        );
    }
    public function restore(int $id){
        $variant=Product_variant::onlyTrashed()->findOrFail($id);
        $variant->restore();
        return $this->successResponse(
            ['variant'=>$variant],
            'Product variant restored successfully ',
            200
        );
    }
}
