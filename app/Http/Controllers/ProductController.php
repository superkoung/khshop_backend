<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Product_variant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        // =========================================================
        // Products
        // =========================================================

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

            // Count unique colors
            ->withCount([
                'variants as total_colors' => function ($v) {
                    $v->select(DB::raw('count(distinct color_id)'));
                }
            ])

            // Load variants + image
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

            // Find main menu
            $menu = Category::where(
                'slug',
                $request->menuSlug
            )->first();

            if ($menu) {

                // -------------------------------------------------
                // Subcategory selected
                // -------------------------------------------------

                if ($request->filled('selectedCategories')) {

                    $selectedCategories = (array) $request->selectedCategories;

                    /*
                 * Find categories that:
                 * 1. Belong to current menu
                 * 2. Match selected category slug(s)
                 */

                    $categoryIds = Category::where(
                        'parent_id',
                        $menu->id
                    )
                        ->whereIn(
                            'slug',
                            $selectedCategories
                        )
                        ->pluck('id');

                    // Show products from selected subcategories
                    $query->whereIn(
                        'category_id',
                        $categoryIds
                    );
                } else {

                    // -------------------------------------------------
                    // Main menu only
                    // -------------------------------------------------

                    $categoryIds = Category::where(
                        'parent_id',
                        $menu->id
                    )->pluck('id');

                    // Show all products under this menu
                    $query->whereIn(
                        'category_id',
                        $categoryIds
                    );
                }
            }
        }

        // =========================================================
        // Search
        // =========================================================

        if ($request->filled('search')) {

            $query->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
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

            $query->where(
                'price',
                '>=',
                $request->min_price
            );
        }

        // =========================================================
        // Max Price
        // =========================================================

        if ($request->filled('max_price')) {

            $query->where(
                'price',
                '<=',
                $request->max_price
            );
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
            }
        } else {

            $query->latest();
        }

        // =========================================================
        // Get Products
        // =========================================================

        $products = $query->get();

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
        $sizes = null;
        $colors = null;
        $brands = null;
        $minPrice = null;
        $maxPrice = null;
        $sort = null;

        $sort = [
            [
                'id' => 1,
                'name' => 'Price High To Low',
                'slug' => 'price_high_low'
            ],
            [
                'id' => 2,
                'name' => 'Price Low To High',
                'slug' => 'price_low_high'
            ],
            [
                'id' => 3,
                'name' => 'New Arrivals',
                'slug' => 'new_arrival'
            ]
        ];

        if ($request->filled('menuSlug')) {
            $parentCategory = Category::where('slug', $request->menuSlug)->first();
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
        if ($request->filled('categorySlug')) {
            $category = Category::where('slug', $request->categorySlug)->first();
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
        return $this->successResponse(
            ['filter' => [
                'sizes' => $sizes,
                'colors' => $colors,
                'brands' => $brands,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'sort' => $sort
            ]]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required',
            'brand_id' => 'required',
            'description' => 'required|string|max:255',
            'price' => 'required',
            'is_active' => 'sometime|boolean'
        ]);

        $validatedData['slug'] = Str::slug($validatedData['name']);

        $products = Product::create($validatedData);

        return $this->successResponse(
            ['products' => $products],
            'Product created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */

    public function show(string $slug)
    {
        // =========================================================
        // 1. Get product + variants + color + size + image
        // =========================================================

        $product = Product::with([
            'variants.color',
            'variants.size',
            'variants.image',
        ])
            ->where('slug', $slug)
            ->firstOrFail();


        // =========================================================
        // 2. Group variants by color
        // =========================================================

        $colors = $product->variants
            ->groupBy('color_id');


        // =========================================================
        // 3. Convert each color group
        // =========================================================

        $colors = $colors->map(function ($variants) {

            // Get color information
            $color = $variants->first()->color;


            // =====================================================
            // Get images
            // =====================================================

            $images = $variants
                ->map(function ($variant) {

                    return $variant->image;
                })
                ->filter()
                ->unique('id')
                ->values();


            // =====================================================
            // Get sizes
            // =====================================================

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

            // =====================================================
            // Return one color
            // =====================================================

            return [
                'id' => $color->id,
                'name' => $color->name,
                'hex' => $color->hex,
                'images' => $images,
                'sizes' => $sizes,
            ];
        })
            ->values();


        // =========================================================
        // 4. Final response
        // =========================================================

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'colors' => $colors,
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
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
        $product = Product::query()->findOrFail($id);
        if (isset($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }
        $product->update($validatedData);
        return $this->successResponse(
            ['product' => $product],
            'Product updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $product = Product::query()->findOrFail($id);
        $product->delete();
        return $this->successResponse(null, 'Product deleted successfully', 200);
    }
    public function forceDelete(int $id)
    {
        Product::onlyTrashed()->findOrFail($id)->forceDelete();
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
        return $this->successResponse(
            ['product' => $product],
            'Product restore successfully',
            200
        );
    }
}
