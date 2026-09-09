<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // =========================================================
        // Homepage banners
        // =========================================================

        $banners = Banner::query()
            ->whereNull('menu_id')
            ->where('is_active', true)
            ->get();

        // =========================================================
        // New Arrivals
        // =========================================================

        $newArrivals = Product::query()
            ->with([
                'variants' => function ($query) {
                    $query->with([
                        'color',
                        'size',
                        'image',
                    ]);
                },
            ])
            ->where('is_active', true)
            ->latest()
            ->paginate(8);

        $newArrivals->through(function ($product) {

            // Get unique colors
            $colors = $product->variants
                ->pluck('color')
                ->filter()
                ->unique('id')
                ->values();

            // First variant
            $firstVariant = $product->variants->first();

            // Remove variants from response
            unset($product->variants);

            // Add formatted data
            $product->first_variant = $firstVariant;
            $product->available_colors = $colors;
            $product->total_colors = $colors->count();

            return $product;
        });

        // =========================================================
        // Special Products
        // =========================================================

        $specialProducts = Product::query()
            ->with([
                'variants' => function ($query) {
                    $query->with([
                        'color',
                        'size',
                        'image',
                    ]);
                },
            ])
            ->where('is_active', true)
            ->whereNotNull('discount_type')
            ->paginate(8);

        $specialProducts->through(function ($product) {

            // Get unique colors
            $colors = $product->variants
                ->pluck('color')
                ->filter()
                ->unique('id')
                ->values();

            // First variant
            $firstVariant = $product->variants->first();

            // Remove variants from response
            unset($product->variants);

            // Add formatted data
            $product->first_variant = $firstVariant;
            $product->available_colors = $colors;
            $product->total_colors = $colors->count();

            return $product;
        });

        // =========================================================
        // Response
        // =========================================================

        return response()->json([
            'banners' => $banners,
            'new_arrivals' => $newArrivals,
            'special_products' => $specialProducts,
        ]);
    }
}

