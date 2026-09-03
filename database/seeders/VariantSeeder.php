<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 💡 1. លុបទិន្នន័យចាស់ទាំងអស់ក្នុងតារាង product_variants និង reset ID
        // ចំណាំ៖ បើជួប Error Foreign key លើ PostgreSQL ត្រូវប្រើ statement ឬ delete()
        DB::statement('TRUNCATE TABLE product_variants CASCADE');

        // 💡 2. ទាញយក Product IDs ដែលមានស្រាប់ក្នុងតារាង products
        $productIds = DB::table('products')->pluck('id');

        $variants = [];

        foreach ($productIds as $index => $productId) {
            for ($variantIndex = 1; $variantIndex <= 2; $variantIndex++) {

                $sizeId = (($index + $variantIndex - 1) % 6) + 1;
                $colorId = (($index + ($variantIndex * 2) - 1) % 4) + 1;

                $sku = 'SKU-P' . $productId . '-S' . $sizeId . '-C' . $colorId . '-V' . $variantIndex;

                $variants[] = [
                    'product_id'     => $productId,
                    'size_id'        => $sizeId,
                    'color_id'       => $colorId,
                    'sku'            => $sku,
                    'stock'          => rand(10, 100),
                    'price_modifier' => ($variantIndex == 2) ? 5.00 : 0.00,
                    'is_active'      => true,
                    'deleted_at'     => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        // 💡 3. Insert ទិន្នន័យថ្មីចូល
        DB::table('product_variants')->insert($variants);
    }
}
