<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rawSizes = [
            "XS", "S", "M", "L", "XL", "XXL", "XXXL",
            "28", "30", "32", "34", "36", "38", "40", "42", "44",
            "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46"
        ];

        $uniqueSizes = array_unique($rawSizes);

        $sizes = array_map(function ($size) {
            return ['name' => $size];
        }, $uniqueSizes);

        Size::insert(array_values($sizes));
    }
}
