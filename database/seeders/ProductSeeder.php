<?php

namespace Database\Seeders;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $products = [];

        // Sub-categories ID (6 to 30)
        $subCategoryIds = range(6, 30);

        // Template សម្រាប់ទិន្នន័យផលិតផល
        $productTemplates = [
            ['name' => 'Cotton T-Shirt', 'price' => 25.00],
            ['name' => 'Casual Polo Shirt', 'price' => 35.00],
            ['name' => 'Running Sneakers', 'price' => 75.00],
            ['name' => 'Classic Leather Shoes', 'price' => 90.00],
            ['name' => 'Denim Pants', 'price' => 45.00],
            ['name' => 'Slim Fit Chino', 'price' => 40.00],
            ['name' => 'Sports Hoodie', 'price' => 50.00],
            ['name' => 'Lightweight Jacket', 'price' => 85.00],
            ['name' => 'Leather Handbag', 'price' => 120.00],
            ['name' => 'Athletic Shorts', 'price' => 30.00],
        ];

        for ($i = 1; $i <= 50; $i++) {
            $template = $productTemplates[($i - 1) % count($productTemplates)];

            // កំណត់ brand_id = 1 សម្រាប់គ្រប់ record ទាំងអស់
            $brandId = 1;

            $categoryId = $subCategoryIds[($i - 1) % count($subCategoryIds)];

            $name = $template['name'] . ' Vol ' . $i;
            $slug = Str::slug($name) . '-' . $i;
            $price = $template['price'];

            $discountType = null;
            $discountValue = null;
            $salePrice = $price;

            if ($i % 3 == 0) {
                $discountType = 'percent';
                $discountValue = 10;
                $salePrice = $price - ($price * 0.10);
            } elseif ($i % 5 == 0) {
                $discountType = 'fixed';
                $discountValue = 5;
                $salePrice = max(0, $price - 5);
            }

            $products[] = [
                'brand_id'       => $brandId,
                'category_id'    => $categoryId,
                'name'           => $name,
                'slug'           => $slug,
                'description'    => "High-quality {$name} suitable for daily use.",
                'price'          => $price,
                'is_active'      => true,
                'deleted_at'     => null,
                'created_at'     => $now,
                'updated_at'     => $now,
                'discount_type'  => $discountType,
                'discount_value' => $discountValue,
                'sale_price'     => $salePrice,
            ];
        }

        DB::table('products')->insert($products);

    }

}
