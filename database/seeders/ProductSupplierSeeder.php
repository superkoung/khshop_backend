<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. បញ្ជី Selling Price នៃ Product ទាំង 67 [product_id => retail_price]
        $productPrices = [
            1 => 120.00, 2 => 100.00, 3 => 100.00, 4 => 165.00, 5 => 70.00,
            6 => 35.00,  7 => 25.00,  8 => 90.00,  9 => 35.00,  10 => 45.00,
            11 => 30.00, 12 => 100.00, 13 => 60.00, 14 => 45.00, 15 => 55.00,
            16 => 140.00, 17 => 120.00, 18 => 100.00, 19 => 90.00, 20 => 120.00,
            21 => 65.00, 22 => 70.00, 23 => 100.00, 24 => 110.00, 25 => 28.00,
            26 => 30.00, 27 => 50.00, 28 => 110.00, 29 => 45.00, 30 => 40.00,
            31 => 55.00, 32 => 100.00, 33 => 60.00, 34 => 65.00, 35 => 110.00,
            36 => 110.00, 37 => 70.00, 38 => 65.00, 39 => 130.00, 40 => 50.00,
            41 => 30.00, 42 => 20.00, 43 => 45.00, 44 => 45.00, 45 => 50.00,
            46 => 55.00, 47 => 75.00, 48 => 40.00, 49 => 40.00, 50 => 45.00,
            51 => 45.00, 52 => 140.00, 53 => 180.00, 54 => 170.00, 55 => 150.00,
            56 => 95.00, 57 => 100.00, 58 => 90.00, 59 => 35.00, 60 => 150.00,
            61 => 130.00, 62 => 140.00, 63 => 35.00, 64 => 85.00, 65 => 150.00,
            66 => 120.00, 67 => 140.00
        ];

        // 2. Mapping product_id ទៅកាន់ supplier_id (1 Brand = 1 Supplier)
        $productSupplierMap = [
            // Nike (Supplier 1)
            1 => 1, 5 => 1, 9 => 1, 13 => 1, 17 => 1, 21 => 1, 25 => 1, 29 => 1, 32 => 1, 36 => 1, 40 => 1, 44 => 1, 48 => 1, 52 => 1, 56 => 1, 60 => 1, 67 => 1,
            // adidas (Supplier 2)
            2 => 2, 6 => 2, 10 => 2, 14 => 2, 18 => 2, 22 => 2, 26 => 2, 33 => 2, 37 => 2, 41 => 2, 45 => 2, 49 => 2, 53 => 2, 57 => 2, 59 => 2, 61 => 2, 66 => 2,
            // New Balance (Supplier 3)
            3 => 3, 11 => 3, 19 => 3, 34 => 3, 38 => 3,
            // ASICS (Supplier 4)
            4 => 4, 54 => 4, 64 => 4,
            // Uniqlo (Supplier 5)
            7 => 5, 42 => 5,
            // Zara (Supplier 6)
            8 => 6, 43 => 6,
            // Calvin Klein (Supplier 7)
            12 => 7,
            // PUMA (Supplier 8)
            15 => 8, 30 => 8, 58 => 8,
            // HOKA (Supplier 9)
            16 => 9, 55 => 9,
            // Air Jordan (Supplier 10)
            20 => 10, 31 => 10, 35 => 10, 39 => 10,
            // Levi's (Supplier 11)
            23 => 11, 46 => 11,
            // Lacoste (Supplier 12)
            24 => 12, 47 => 12,
            // Vans (Supplier 13)
            27 => 13, 50 => 13,
            // The North Face (Supplier 14)
            28 => 14,
            // Under Armour (Supplier 15)
            51 => 15, 63 => 15,
            // Reebok (Supplier 16)
            62 => 16, 65 => 16
        ];

        $data = [];

        foreach ($productSupplierMap as $productId => $supplierId) {
            $retailPrice = $productPrices[$productId] ?? 100.00;
            // គណនា Cost Price ត្រឹម 60% នៃ Selling Price (ចំណេញ margin 40%)
            $costPrice = round($retailPrice * 0.60, 2);

            $data[] = [
                'product_id' => $productId,
                'supplier_id' => $supplierId,
                'supplier_sku' => 'SUP-' . str_pad($supplierId, 2, '0', STR_PAD_LEFT) . '-P' . str_pad($productId, 3, '0', STR_PAD_LEFT),
                'cost_price' => $costPrice,
                'is_primary' => true,
            ];
        }

        DB::table('product_suppliers')->insert($data);
    }
}
