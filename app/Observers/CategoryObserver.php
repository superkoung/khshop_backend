<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    // ១. ឱ្យតែមានការ បង្កើតថ្មី ឬ កែប្រែ data
    public function saved(Category $category): void
    {
        Cache::forget('nav_menu');
    }

    // ២. ឱ្យតែមានការ លុប data
    public function deleted(Category $category): void
    {
        Cache::forget('nav_menu');
    }
}
