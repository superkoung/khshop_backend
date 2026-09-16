<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'Nike', 'adidas', 'New Balance', 'ASICS', 'Uniqlo',
            'Zara', 'Calvin Klein', 'PUMA', 'HOKA', 'Air Jordan',
            'Levi\'s', 'Lacoste', 'Vans', 'The North Face', 'Under Armour', 'Reebok'
        ];

        foreach ($brands as $index => $brand) {
            Supplier::create([
                'id' => $index + 1,
                'name' => "{$brand} Official Supplier Co., Ltd.",
                'contact_name' => "Sales Manager ({$brand})",
                'phone' => '012 ' . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . ' 789',
                'email' => strtolower(str_replace([' ', '\''], '', $brand)) . '@supplier-distributor.com',
                'address' => 'Phnom Penh, Cambodia',
                'is_active' => true,
                'notes' => "Official primary supplier and authorized distributor for {$brand} products.",
            ]);
        }
    }
}
