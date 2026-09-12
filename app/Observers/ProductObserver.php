<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function saved(Product $product): void {
        // បើប្រើ Redis អាចប្រើ Tags បាន
        // Cache::tags(['homepage_products'])->flush();

        // បើប្រើ File Driver ធម្មតា៖
        Cache::flush(); // ឬទុកឱ្យវា Expire តាមម៉ោងកំណត់ (3600s)
    }
}
