<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Observers\BannerObserver;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Category::observe(CategoryObserver::class);
        Banner::observe(BannerObserver::class);
        Product::observe(ProductObserver::class);

    }
}
