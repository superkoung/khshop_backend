<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
       public function index()
    {
        // Homepage banners
        $banners = Banner::query()
            ->whereNull('menu_id')
            ->where('is_active', true)
            ->get();

        // New arrivals
        $newArrivals = Product::query()
            ->where('is_active', true)
            ->latest()
            ->take(8)
            ->get();

        // Parent categories
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->get();

        return response()->json([
            'banners' => $banners,
            'new_arrivals' => $newArrivals,
            'categories' => $categories,
        ]);
    }
}
