<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // =========================================================
        // Homepage banners (cache 24 ម៉ោង - ព័ត៌មានស្ថិតស្ថេរខ្លាំង)
        // =========================================================

        $banners = Cache::remember('homepage_banners', now()->addDay(), function () {
            return Banner::query()
                ->whereNull('menu_id')
                ->where('is_active', true)
                ->get();
        });

        // =========================================================
        // New Arrivals (cache 6 ម៉ោង - តាម page)
        // =========================================================

        $newArrivalsPage = $request->input('new_arrivals_page', 1);

        $newArrivals = Cache::remember(
            "new_arrivals_page_{$newArrivalsPage}",
            now()->addHours(6),
            function () {
                return $this->getFormattedProducts(
                    Product::query()->latest(),
                    'new_arrivals_page'
                );
            }
        );

        // =========================================================
        // Special Products (cache 6 ម៉ោង - តាម page)
        // =========================================================

        $specialProductsPage = $request->input('special_products_page', 1);

        $specialProducts = Cache::remember(
            "special_products_page_{$specialProductsPage}",
            now()->addHours(6),
            function () {
                return $this->getFormattedProducts(
                    Product::query()->whereNotNull('discount_type'),
                    'special_products_page'
                );
            }
        );

        // =========================================================
        // Response
        // =========================================================

        return response()->json([
            'banners' => $banners,
            'new_arrivals' => $newArrivals,
            'special_products' => $specialProducts,
        ]);
    }

    private function getFormattedProducts($query, string $pageName)
    {
        $products = $query
            ->where('is_active', true)
            ->with([
                'variants' => function ($q) {
                    $q->with(['color', 'size', 'image']);
                },
            ])
            ->paginate(8, ['*'], $pageName);

        $products->through(function ($product) {

            $colors = $product->variants
                ->pluck('color')
                ->filter()
                ->unique('id')
                ->values();

            $firstVariant = $product->variants->first();

            unset($product->variants);

            $product->first_variant = $firstVariant;
            $product->available_colors = $colors;
            $product->total_colors = $colors->count();

            return $product;
        });

        return $products;
    }
}
