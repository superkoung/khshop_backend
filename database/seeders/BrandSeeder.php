<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Nike', 'slug' => 'nike'],
            ['name' => 'Adidas', 'slug' => 'adidas'],
            ['name' => 'Puma', 'slug' => 'puma'],
            ['name' => 'Under Armour', 'slug' => 'under-armour'],
            ['name' => 'New Balance', 'slug' => 'new-balance'],
            ['name' => 'ASICS', 'slug' => 'asics'],
            ['name' => 'Skechers', 'slug' => 'skechers'],
            ['name' => 'Converse', 'slug' => 'converse'],
            ['name' => 'Vans', 'slug' => 'vans'],
            ['name' => 'Reebok', 'slug' => 'reebok'],
            ['name' => 'Jordan', 'slug' => 'jordan'],
            ['name' => 'HOKA', 'slug' => 'hoka'],
            ['name' => 'Levis', 'slug' => 'levi'],
            ['name' => 'Uniqlo', 'slug' => 'uniqlo'],
            ['name' => 'Zara', 'slug' => 'zara'],
            ['name' => 'H&M', 'slug' => 'hm'],
            ['name' => 'Lacoste', 'slug' => 'lacoste'],
            ['name' => 'Calvin Klein', 'slug' => 'calvin-klein'],
            ['name' => 'The North Face', 'slug' => 'the-north-face'],
            ['name' => 'Columbia', 'slug' => 'columbia'],
            ['name' => 'Mizuno', 'slug' => 'mizuno'],
        ];
        Brand::insert($brands);
    }
}
