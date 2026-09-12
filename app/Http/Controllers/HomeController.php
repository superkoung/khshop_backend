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
        // 1. បង្កើត Integer Sanitized Page Numbers
        $pageNew = (int) $request->get('page_new', $request->get('page', 1));
        $pageSpecial = (int) $request->get('page_special', $request->get('page', 1));

        // 2. Banners (Cache 1 ថ្ងៃ ប្រើ Tag 'banners')
        $banners = Cache::tags(['banners', 'homepage'])->remember('home_banners', 86400, function () {
            return Banner::query()
                ->whereNull('menu_id')
                ->where('is_active', true)
                ->get();
        });

        // 3. New Arrivals (Cache 1 ម៉ោង ប្រើ Tag 'products')
        $newArrivals = Cache::tags(['products', 'homepage'])->remember("home_new_arrivals_p{$pageNew}", 3600, function () use ($pageNew) {
            return $this->getPaginatedProducts('latest', $pageNew);
        });

        // 4. Special Products (Cache 1 ម៉ោង ប្រើ Tag 'products')
        $specialProducts = Cache::tags(['products', 'homepage'])->remember("home_special_products_p{$pageSpecial}", 3600, function () use ($pageSpecial) {
            return $this->getPaginatedProducts('special', $pageSpecial);
        });

        return response()->json([
            'banners' => $banners,
            'new_arrivals' => $newArrivals,
            'special_products' => $specialProducts,
        ]);
    }

    /**
     * Helper Function សម្រាប់រៀបចំ Data Product កាត់បន្ថយ Duplicate Code
     */
    private function getPaginatedProducts(string $type, int $page)
    {
        $query = Product::query()
            ->with([
                'variants' => function ($query) {
                    $query->with(['color', 'size', 'image']);
                },
            ])
            ->where('is_active', true);

        if ($type === 'latest') {
            $query->latest();
        } elseif ($type === 'special') {
            $query->whereNotNull('discount_type');
        }

        // ប្រើ page query ជាក់ស្តែង
        $products = $query->paginate(8, ['*'], 'page', $page);

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
