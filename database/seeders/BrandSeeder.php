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
        $brands=[
            [
                "name"=>"Adidas",
                "slug"=>"adidas",
                "image_path"=>"1111.jpg"
            ]
        ];
        Brand::insert($brands);
    }
}
