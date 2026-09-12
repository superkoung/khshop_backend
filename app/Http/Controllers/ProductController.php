<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Product_variant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource with Redis Cache.
     */
    public function index(Request $request)
    {
        // 1. បង្កើត Cache Key ដោយផ្អែកលើ URL Query String ទាំងអស់ (ឧ. ?page=1&search=shoe&sort=price_low_high)
        $cacheKey = 'products_list_' . md5(json_encode($request->all()));

        // 2. ប្រើ Cache Tags ដើម្បីស្រួល Clear ពេលមានការកែប្រែ Product
        $products = Cache::tags(['products'])->remember($cacheKey, 3600, function () use ($request) {

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
                        )->with(['image', 'color', 'size']);
                    }
                ])
                ->where('is_active', true);

            // Filter Menu + Subcategory
            if ($request->filled('menuSlug')) {
                $menu = Category::where('slug', $request->menuSlug)->first();

                if ($menu) {
                    if ($request->filled('selectedCategories')) {
                        $selectedCategories = (array) $request->selectedCategories;

                        $categoryIds = Category::where('parent_id', $menu->id)
                            ->whereIn('slug', $selectedCategories)
                            ->pluck('id');

                        $query->whereIn('category_id', $categoryIds);
                    } else {
                        $categoryIds = Category::where('parent_id', $menu->id)->pluck('id');
                        $query->whereIn('category_id', $categoryIds);
                    }
                }
            }

            // Search Filter
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Size Filter
            if ($request->filled('selectedSizes')) {
                $sizes = (array) $request->selectedSizes;
                $query->whereHas('variants', fn($q) => $q->whereIn('size_id', $sizes));
            }

            // Color Filter
            if ($request->filled('selectedColors')) {
                $colors = (array) $request->selectedColors;
                $query->whereHas('variants', fn($q) => $q->whereIn('color_id', $colors));
            }

            // Price Filters
            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting
            if ($request->filled('sort')) {
                if ($request->sort === 'price_low_high') {
                    $query->orderBy('price', 'asc');
                } elseif ($request->sort === 'price_high_low') {
                    $query->orderBy('price', 'desc');
                } elseif ($request->sort === 'new_arrival') {
                    $query->latest();
                }
            } else {
                $query->latest();
            }

            return $query->get();
        });

        return $this->successResponse(['products' => $products], 'Get products data', 200);
    }

    /**
     * Get Filter Data with Redis Cache.
     */
    public function getFilter(Request $request)
    {
        $cacheKey = 'products_filters_' . md5(json_encode($request->all()));

        $filterData = Cache::tags(['products', 'categories'])->remember($cacheKey, 3600, function () use ($request) {
            $sizes = null;
            $colors = null;
            $brands = null;
            $minPrice = null;
            $maxPrice = null;

            $sort = [
                ['id' => 1, 'name' => 'Price High To Low', 'slug' => 'price_high_low'],
                ['id' => 2, 'name' => 'Price Low To High', 'slug' => 'price_low_high'],
                ['id' => 3, 'name' => 'New Arrivals', 'slug' => 'new_arrival']
            ];

            if ($request->filled('menuSlug')) {
                $parentCategory = Category::where('slug', $request->menuSlug)->first();
                if ($parentCategory) {
                    $childCategoryIds = Category::where('parent_id', $parentCategory->id)->pluck('id');
                    $variants = Product_variant::with(['size', 'color'])->whereHas('product', fn($q) => $q->whereIn('category_id', $childCategoryIds))->get();

                    $sizes = $variants->pluck('size')->filter()->unique('id')->values();
                    $colors = $variants->pluck('color')->filter()->unique('id')->values();
                    $brands = Brand::whereHas('products', fn($q) => $q->whereIn('category_id', $childCategoryIds))->get();
                    $minPrice = Product::whereIn('category_id', $childCategoryIds)->min('price');
                    $maxPrice = Product::whereIn('category_id', $childCategoryIds)->max('price');
                }
            }

            if ($request->filled('categorySlug')) {
                $category = Category::where('slug', $request->categorySlug)->first();
                if ($category) {
                    $variants = Product_variant::with(['size', 'color'])->whereHas('product', fn($q) => $q->where('category_id', $category->id))->get();

                    $sizes = $variants->pluck('size')->filter()->unique('id')->values();
                    $colors = $variants->pluck('color')->filter()->unique('id')->values();
                    $brands = Brand::whereHas('products', fn($q) => $q->where('category_id', $category->id))->get();
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
                'sort' => $sort
            ];
        });

        return $this->successResponse(['filter' => $filterData]);
    }

    /**
     * Display Single Product Detail with Redis Cache.
     */
    public function show(string $slug)
    {
        $cacheKey = 'product_detail_' . $slug;

        $productData = Cache::tags(['products'])->remember($cacheKey, 3600, function () use ($slug) {
            $product = Product::with([
                'variants.color',
                'variants.size',
                'variants.image',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

            $colors = $product->variants->groupBy('color_id')->map(function ($variants) {
                $color = $variants->first()->color;

                $images = $variants->map(fn($v) => $v->image)->filter()->unique('id')->values();

                $sizes = $variants->map(function ($variant) {
                    if (!$variant->size) return null;

                    return [
                        'id' => $variant->size->id,
                        'variant_id' => $variant->id,
                        'name' => $variant->size->name,
                        'stock' => $variant->stock,
                    ];
                })->filter()->values();

                return [
                    'id' => $color->id,
                    'name' => $color->name,
                    'hex' => $color->hex,
                    'images' => $images,
                    'sizes' => $sizes,
                ];
            })->values();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'colors' => $colors,
            ];
        });

        return response()->json($productData);
    }

    /**
     * Clear Cache when mutating data.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required',
            'brand_id' => 'required',
            'description' => 'required|string|max:255',
            'price' => 'required',
            'is_active' => 'sometimes|boolean'
        ]);

        $validatedData['slug'] = Str::slug($validatedData['name']);

        $products = Product::create($validatedData);

        // Clear Cache Tags
        Cache::tags(['products'])->flush();

        return $this->successResponse(['products' => $products], 'Product created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required',
            'gender_id' => 'sometimes|required',
            'brand_id' => 'sometimes|required',
            'description' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required',
            'is_active' => 'sometimes|boolean'
        ]);

        $product = Product::findOrFail($id);

        if (isset($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        $product->update($validatedData);

        // Clear Cache Tags
        Cache::tags(['products'])->flush();

        return $this->successResponse(['product' => $product], 'Product updated successfully', 200);
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        // Clear Cache Tags
        Cache::tags(['products'])->flush();

        return $this->successResponse(null, 'Product deleted successfully', 200);
    }

    public function forceDelete(int $id)
    {
        Product::onlyTrashed()->findOrFail($id)->forceDelete();

        // Clear Cache Tags
        Cache::tags(['products'])->flush();

        return $this->successResponse(null, 'Product permanently deleted', 200);
    }

    public function trashed()
    {
        $products = Product::onlyTrashed()->paginate(10);
        return $this->successResponse(['products' => $products], 'Get product trashed', 200);
    }

    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        // Clear Cache Tags
        Cache::tags(['products'])->flush();

        return $this->successResponse(['product' => $product], 'Product restore successfully', 200);
    }
}
