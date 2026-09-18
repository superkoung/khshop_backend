<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CollectionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Collection::withCount('products');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active') && $request->is_active !== 'all') {
            $isActive = $request->is_active === '1' || $request->is_active === 'true';
            $query->where('is_active', $isActive);
        }

        $collections = $query->latest()->get();

        return $this->successResponse(
            ['collections' => $collections],
            'Collections retrieved successfully',
            200
        );
    }

    public function show(int $id)
    {
        $collection = Collection::withCount('products')
            ->with([
                'products' => function ($q) {
                    $q->select(
                        'products.id',
                        'products.name',
                        'products.slug',
                        'products.price',
                        'products.is_active',
                        'products.category_id'
                    )->with('category:id,name');
                },
            ])->find($id);

        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        return $this->successResponse(
            ['collection' => $collection],
            'Collection retrieved successfully',
            200
        );
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'image'       => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'is_active'   => 'sometimes|boolean',
            'sort_order'  => 'sometimes|integer|min:0',
        ]);

        $validatedData['slug'] = Str::slug($validatedData['name']);

        $existing = Collection::where('slug', $validatedData['slug'])->first();
        if ($existing) {
            $validatedData['slug'] = $validatedData['slug'] . '-' . time();
        }

        $uploadedImage = $request->file('image')->storeOnCloudinary('collection_images');
        $validatedData['image_path'] = $uploadedImage->getSecurePath();

        if (!isset($validatedData['is_active'])) {
            $validatedData['is_active'] = true;
        }
        if (!isset($validatedData['sort_order'])) {
            $validatedData['sort_order'] = 0;
        }

        $collection = Collection::create($validatedData);

        return $this->successResponse(
            ['collection' => $collection],
            'Collection created successfully',
            201
        );
    }

    public function update(Request $request, int $id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        $validatedData = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'image'       => 'sometimes|image|mimes:png,jpg,jpeg,webp|max:2048',
            'is_active'   => 'sometimes|boolean',
            'sort_order'  => 'sometimes|integer|min:0',
        ]);

        if (isset($validatedData['name']) && $validatedData['name'] !== $collection->name) {
            $newSlug = Str::slug($validatedData['name']);
            $existing = Collection::where('slug', $newSlug)->where('id', '!=', $id)->first();
            $validatedData['slug'] = $existing ? $newSlug . '-' . time() : $newSlug;
        }

        if ($request->hasFile('image')) {
            $uploadedImage = $request->file('image')->storeOnCloudinary('collection_images');
            $validatedData['image_path'] = $uploadedImage->getSecurePath();
        }

        $collection->update($validatedData);

        return $this->successResponse(
            ['collection' => $collection],
            'Collection updated successfully',
            200
        );
    }

    public function destroy(int $id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        $collection->products()->detach();
        $collection->delete();

        return $this->successResponse(null, 'Collection deleted successfully', 200);
    }

    public function updateStatus(Request $request, int $id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $collection->update(['is_active' => $validated['is_active']]);

        return $this->successResponse(
            ['collection' => $collection],
            'Collection status updated successfully',
            200
        );
    }

    public function assignProducts(Request $request, int $id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        $validated = $request->validate([
            'product_ids'   => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $collection->products()->sync($validated['product_ids']);

        return $this->successResponse(
            ['collection' => $collection->load('products')],
            'Products assigned to collection successfully',
            200
        );
    }

    public function removeProduct(int $id, int $productId)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        $collection->products()->detach($productId);

        return $this->successResponse(
            ['collection' => $collection->load('products')],
            'Product removed from collection successfully',
            200
        );
    }
}
