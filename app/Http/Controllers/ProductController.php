<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Product_variant;
use App\Models\Size;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;
    // Customer API
    public function index(Request $request)
    {
        // =========================================================
        // Cache key តាម filter ដែលបានផ្ញើមក
        // =========================================================

        $cacheKey = 'products_index_' . md5(json_encode($request->all()));

        $products = Cache::remember($cacheKey, now()->addHours(6), function () use ($request) {

            $query = Product::query()
                ->select(
                    'id',
                    'name',
                    'slug',
                    'price',
                    'discount_type',
                    'discount_value',
                    'sale_price',
                    'is_active'
                )
                ->withCount([
                    'variants as total_colors' => function ($v) {
                        $v->select(DB::raw('count(distinct color_id)'));
                    }
                ])
                ->with([
                    'variants' => function ($v) {
                        $v->select(
                            'id',
                            'product_id',
                            'color_id',
                            'size_id',
                            'image_id',
                            'price_modifier',
                            'stock'
                        )->with([
                            'image',
                            'color',
                            'size',
                        ]);
                    }
                ])
                ->where('is_active', true);

            // =========================================================
            // Menu + Subcategory
            // =========================================================

            if ($request->filled('menuSlug')) {

                if ($request->menuSlug === 'sale') {

                    $query->where('discount_value', '>', 0);
                } else {

                    $menu = Category::where('slug', $request->menuSlug)->first();

                    if ($menu) {

                        if ($request->filled('selectedCategories')) {

                            $selectedCategories = (array) $request->selectedCategories;

                            $categoryIds = Category::where('parent_id', $menu->id)
                                ->whereIn('slug', $selectedCategories)
                                ->pluck('id');

                            $query->whereIn('category_id', $categoryIds);
                        } else {

                            $categoryIds = Category::where('parent_id', $menu->id)
                                ->pluck('id');

                            $query->whereIn('category_id', $categoryIds);
                        }
                    }
                }
            }

            // =========================================================
            // Search
            // =========================================================

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // =========================================================
            // Size
            // =========================================================

            if ($request->filled('selectedSizes')) {
                $sizes = (array) $request->selectedSizes;
                $query->whereHas('variants', function ($q) use ($sizes) {
                    $q->whereIn('size_id', $sizes);
                });
            }

            // =========================================================
            // Brand
            // =========================================================

            if ($request->filled('selectedBrands')) {
                $brands = (array) $request->selectedBrands;
                $query->whereIn('brand_id', $brands);
            }

            // =========================================================
            // Color
            // =========================================================

            if ($request->filled('selectedColors')) {
                $colors = (array) $request->selectedColors;
                $query->whereHas('variants', function ($q) use ($colors) {
                    $q->whereIn('color_id', $colors);
                });
            }

            // =========================================================
            // Min Price
            // =========================================================

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            // =========================================================
            // Max Price
            // =========================================================

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // =========================================================
            // Sort
            // =========================================================

            if ($request->filled('sort')) {

                if ($request->sort === 'price_low_high') {
                    $query->orderBy('price', 'asc');
                } elseif ($request->sort === 'price_high_low') {
                    $query->orderBy('price', 'desc');
                } elseif ($request->sort === 'new_arrival') {
                    $query->latest();
                } else {
                    $query->latest();
                }
            } else {
                $query->latest();
            }

            return $query->get();
        });

        return $this->successResponse(
            [
                'products' => $products,
            ],
            'Get products data',
            200
        );
    }

    public function getFilter(Request $request)
    {
        $cacheKey = 'products_filter_' . md5(json_encode($request->all()));

        $result = Cache::remember($cacheKey, now()->addHours(6), function () use ($request) {

            $sizes = null;
            $colors = null;
            $brands = null;
            $minPrice = null;
            $maxPrice = null;

            if ($request->filled('menuSlug')) {

                $parentCategory = Category::where('slug', $request->menuSlug)->first();

                // FIX: null check ជៀសវាង error "Attempt to read property 'id' on null"
                if ($parentCategory) {

                    $childCategoryIds = Category::where('parent_id', $parentCategory->id)->pluck('id');

                    $variants = Product_variant::with(['size', 'color'])->whereHas('product', function ($q) use ($childCategoryIds) {
                        $q->whereIn('category_id', $childCategoryIds);
                    })->get();

                    $sizes = $variants->pluck('size')->filter()->unique('id')->values();
                    $colors = $variants->pluck('color')->filter()->unique('id')->values();

                    $brands = Brand::whereHas('products', function ($q) use ($childCategoryIds) {
                        $q->whereIn('category_id', $childCategoryIds);
                    })->get();

                    $minPrice = Product::whereIn('category_id', $childCategoryIds)->min('price');
                    $maxPrice = Product::whereIn('category_id', $childCategoryIds)->max('price');
                }
            }

            if ($request->filled('categorySlug')) {

                $category = Category::where('slug', $request->categorySlug)->first();

                // FIX: null check
                if ($category) {

                    $variants = Product_variant::with(['size', 'color'])->whereHas('product', function ($q) use ($category) {
                        $q->where('category_id', $category->id);
                    })->get();

                    $sizes = $variants->pluck('size')->filter()->unique('id')->values();
                    $colors = $variants->pluck('color')->filter()->unique('id')->values();

                    $brands = Brand::whereHas('products', function ($q) use ($category) {
                        $q->where('category_id', $category->id);
                    })->get();

                    $minPrice = Product::where('category_id', $category->id)->min('price');
                    $maxPrice = Product::where('category_id', $category->id)->max('price');
                }
            }

            return [
                'sizes' => $sizes,
                'colors' => $colors,
                'brands' => $brands,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
            ];
        });

        $sort = [
            ['id' => 1, 'name' => 'Price High To Low', 'slug' => 'price_high_low'],
            ['id' => 2, 'name' => 'Price Low To High', 'slug' => 'price_low_high'],
            ['id' => 3, 'name' => 'New Arrivals', 'slug' => 'new_arrival'],
        ];

        return $this->successResponse(
            ['filter' => array_merge($result, ['sort' => $sort])]
        );
    }

    public function show(string $slug)
    {
        $product = Product::with([
            'variants.color',
            'variants.size',
            'variants.image',
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        $colors = $product->variants
            ->groupBy('color_id');

        $colors = $colors->map(function ($variants) {

            $color = $variants->first()->color;

            $images = $variants
                ->map(function ($variant) {
                    return $variant->image;
                })
                ->filter()
                ->unique('id')
                ->values();

            $sizes = $variants
                ->map(function ($variant) {

                    if (!$variant->size) {
                        return null;
                    }

                    return [
                        'id' => $variant->size->id,
                        'variant_id' => $variant->id,
                        'name' => $variant->size->name,
                        'stock' => $variant->stock,
                    ];
                })
                ->filter()
                ->values();

            return [
                'id' => $color->id,
                'name' => $color->name,
                'hex' => $color->code,
                'images' => $images,
                'sizes' => $sizes,
            ];
        })
            ->values();

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'colors' => $colors,
        ]);
    }

    // Admin api
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            // Product
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // Variants
            'variants' => ['required', 'array', 'min:1'],

            'variants.*.size_id' => [
                'nullable',
                'integer',
                'exists:sizes,id',
            ],

            'variants.*.color_id' => [
                'nullable',
                'integer',
                'exists:colors,id',
            ],

            'variants.*.sku' => [
                'required',
                'string',
                'max:100',
            ],

            'variants.*.stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'variants.*.price_modifier' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'variants.*.is_active' => [
                'nullable',
                'boolean',
            ],

            // Variant image (file upload)
            'variants.*.image' => [
                'nullable',
                'image',
                'max:5120',
            ],
        ]);

        try {
            $product = DB::transaction(function () use ($validated, $request) {

                $product = Product::create([
                    'name' => $validated['name'],
                    'slug' => $validated['slug'] ?? Str::slug($validated['name']),
                    'category_id' => $validated['category_id'] ?? null,
                    'brand_id' => $validated['brand_id'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'price' => $validated['price'],
                    'discount_type' => $validated['discount_type'] ?? null,
                    'discount_value' => $validated['discount_value'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                foreach ($validated['variants'] as $index => $variantData) {

                    $imageId = null;

                    if ($request->hasFile("variants.$index.image")) {
                        $file = $request->file("variants.$index.image");
                        $filename = time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('products', $filename, 'public');

                        $imageRecord = Image::create([
                            'name' => $filename,
                            'image_path' => '/storage/' . $path,
                        ]);

                        $imageId = $imageRecord->id;
                    }

                    Product_variant::create([
                        'product_id' => $product->id,
                        'size_id' => $variantData['size_id'] ?? null,
                        'color_id' => $variantData['color_id'] ?? null,
                        'image_id' => $imageId,
                        'sku' => $variantData['sku'],
                        'stock' => $variantData['stock'],
                        'price_modifier' => $variantData['price_modifier'] ?? 0,
                        'is_active' => $variantData['is_active'] ?? true,
                    ]);
                }

                return $product;
            });

            $product->load([
                'variants.size',
                'variants.color',
                'variants.image',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => $product,
            ], 201);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // admin API
    public function adminIndex(Request $request)
    {
        $query = Product::query()
            ->with([
                'category',
                'variants.image',
                'variants.size',
                'variants.color',
            ])
            ->withSum('variants as total_stock', 'stock');

        // Search product
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', "%{$search}%");
                    });
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Status filter
        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Latest products first
        $products = $query
            ->latest()
            ->orderBy('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return $this->successResponse(
            [
                'products' => $products,
            ],
            'Products retrieved successfully',
            200
        );
    }

    public function adminShow(int $id)
    {
        $product = Product::query()
            ->with([
                'category',
                'brand',
                'variants.image',
                'variants.size',
                'variants.color',
            ])
            ->withSum('variants as total_stock', 'stock')
            ->findOrFail($id);

        return $this->successResponse(
            [
                'product' => $product,
            ],
            'Product retrieved successfully',
            200
        );
    }

    public function adminUpdate(Request $request, int $id)
    {
        $product = Product::query()->findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'brand_id' => 'sometimes|nullable|integer|exists:brands,id',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'discount_type' => 'sometimes|nullable|in:percent,fixed',
            'discount_value' => 'sometimes|nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',

            // Variants (optional on update)
            'variants' => 'sometimes|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.size_id' => 'nullable|integer|exists:sizes,id',
            'variants.*.color_id' => 'nullable|integer|exists:colors,id',
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.stock' => 'required_with:variants|integer|min:0',
            'variants.*.price_modifier' => 'nullable|numeric|min:0',
            'variants.*.is_active' => 'nullable|boolean',
            'variants.*.image' => 'nullable|image|max:5120',
        ]);

        if (isset($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        DB::transaction(function () use ($product, $validatedData, $request) {

            $product->update(collect($validatedData)->only([
                'name', 'slug', 'category_id', 'brand_id', 'description',
                'price', 'discount_type', 'discount_value', 'is_active',
            ])->toArray());

            if (isset($validatedData['variants'])) {
                $submittedIds = [];

                foreach ($validatedData['variants'] as $index => $variantData) {

                    $imageId = null;
                    if ($request->hasFile("variants.$index.image")) {
                        $file = $request->file("variants.$index.image");
                        $filename = time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('products', $filename, 'public');

                        $imageRecord = Image::create([
                            'name' => $filename,
                            'image_path' => '/storage/' . $path,
                        ]);

                        $imageId = $imageRecord->id;
                    }

                    $updateData = [
                        'size_id' => $variantData['size_id'] ?? null,
                        'color_id' => $variantData['color_id'] ?? null,
                        'sku' => $variantData['sku'],
                        'stock' => $variantData['stock'],
                        'price_modifier' => $variantData['price_modifier'] ?? 0,
                        'is_active' => $variantData['is_active'] ?? true,
                    ];

                    if ($imageId !== null) {
                        $updateData['image_id'] = $imageId;
                    }

                    if (!empty($variantData['id'])) {
                        // Existing variant — update it
                        $variant = Product_variant::where('id', $variantData['id'])
                            ->where('product_id', $product->id)
                            ->first();

                        if ($variant) {
                            $variant->update($updateData);
                            $submittedIds[] = $variant->id;
                        }
                    } else {
                        // New variant — create it
                        $newVariant = Product_variant::create(array_merge($updateData, [
                            'product_id' => $product->id,
                        ]));
                        $submittedIds[] = $newVariant->id;
                    }
                }

                // Delete variants that were not submitted
                if (!empty($submittedIds)) {
                    Product_variant::where('product_id', $product->id)
                        ->whereNotIn('id', $submittedIds)
                        ->delete();
                }
            }
        });

        Cache::flush();

        $product->load([
            'category',
            'brand',
            'variants.image',
            'variants.size',
            'variants.color',
        ]);

        return $this->successResponse(
            [
                'product' => $product,
            ],
            'Product updated successfully',
            200
        );
    }

    public function adminDestroy(int $id)
    {
        $product = Product::query()->findOrFail($id);
        $product->delete();

        // Clear cache ព្រោះ product ត្រូវបានលុប
        Cache::flush();

        return $this->successResponse(null, 'Product deleted successfully', 200);
    }

    public function forceDelete(int $id)
    {
        Product::onlyTrashed()->findOrFail($id)->forceDelete();
        Cache::flush();
        return $this->successResponse(null, 'Product permanently deleted', 200);
    }

    public function trashed()
    {
        $products = Product::onlyTrashed()->paginate(10);

        return $this->successResponse(
            ['products' => $products],
            'Get product trashed',
            200
        );
    }

    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();
        Cache::flush();
        return $this->successResponse(
            ['product' => $product],
            'Product restore successfully',
            200
        );
    }

    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'category_id' => 'required',
    //         'brand_id' => 'required',
    //         'description' => 'required|string|max:255',
    //         'price' => 'required',
    //         'is_active' => 'sometime|boolean'
    //     ]);

    //     $validatedData['slug'] = Str::slug($validatedData['name']);

    //     $products = Product::create($validatedData);

    //     // Clear cache ព្រោះមាន product ថ្មី
    //     Cache::flush();

    //     return $this->successResponse(
    //         ['products' => $products],
    //         'Product created successfully',
    //         201
    //     );
    // }

    // public function update(Request $request, int $id)
    // {
    //     $validatedData = $request->validate([
    //         'name' => 'sometimes|required|string|max:255',
    //         'category_id' => 'sometimes|required',
    //         'gender_id' => 'sometimes|required',
    //         'brand_id' => 'sometimes|required',
    //         'description' => 'sometimes|required|string|max:255',
    //         'price' => 'sometimes|required',
    //         'is_active' => 'sometimes|boolean'
    //     ]);
    //     $product = Product::query()->findOrFail($id);
    //     if (isset($validatedData['name'])) {
    //         $validatedData['slug'] = Str::slug($validatedData['name']);
    //     }
    //     $product->update($validatedData);

    //     // Clear cache ព្រោះ product ត្រូវបានកែ
    //     Cache::flush();

    //     return $this->successResponse(
    //         ['product' => $product],
    //         'Product updated successfully',
    //         200
    //     );
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  */

    public function adminColors(Request $request)
    {
        $query = Color::orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $colors = $query->paginate($request->integer('per_page', 15));
        return $this->successResponse(['colors' => $colors], 'Colors retrieved', 200);
    }

    public function adminSizes(Request $request)
    {
        $query = Size::orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sizes = $query->paginate($request->integer('per_page', 15));
        return $this->successResponse(['sizes' => $sizes], 'Sizes retrieved', 200);
    }

    public function adminBrands(Request $request)
    {
        $query = Brand::orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $brands = $query->paginate($request->integer('per_page', 15));
        return $this->successResponse(['brands' => $brands], 'Brands retrieved', 200);
    }

    public function adminCategories(Request $request)
    {
        $query = Category::orderBy('name')->whereNull('parent_id');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->paginate($request->integer('per_page', 15));
        return $this->successResponse(['categories' => $categories], 'Categories retrieved', 200);
    }
}
