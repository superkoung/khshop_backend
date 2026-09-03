<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors=[
            [
                "name"=>"Red",
                "code"=>"1111"
            ],
            [
                "name"=>"Blue",
                "code"=>"2222"
            ],
            [
                "name"=>"White",
                "code"=>"3333"
            ],
            [
                "name"=>"Black",
                "code"=>"4444"
            ]
        ];
        Color::insert($colors);
    }
}
