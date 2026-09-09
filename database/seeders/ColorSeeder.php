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
        $colors = [
            ['name' => 'Black',        'code' => 'black'],
            ['name' => 'White',        'code' => 'white'],
            ['name' => 'Off White',    'code' => 'off-white'],
            ['name' => 'Cream',        'code' => 'cream'],
            ['name' => 'Beige',        'code' => 'beige'],
            ['name' => 'Camel',        'code' => 'camel'],
            ['name' => 'Brown',        'code' => 'brown'],
            ['name' => 'Chocolate',    'code' => 'chocolate'],

            ['name' => 'Grey',         'code' => 'grey'],
            ['name' => 'Charcoal',     'code' => 'charcoal'],
            ['name' => 'Silver',       'code' => 'silver'],

            ['name' => 'Navy',         'code' => 'navy'],
            ['name' => 'Blue',         'code' => 'blue'],
            ['name' => 'Light Blue',   'code' => 'light-blue'],
            ['name' => 'Dark Blue',    'code' => 'dark-blue'],
            ['name' => 'Denim',        'code' => 'denim'],

            ['name' => 'Red',          'code' => 'red'],
            ['name' => 'Burgundy',     'code' => 'burgundy'],
            ['name' => 'Maroon',       'code' => 'maroon'],

            ['name' => 'Pink',         'code' => 'pink'],
            ['name' => 'Light Pink',   'code' => 'light-pink'],
            ['name' => 'Rose',         'code' => 'rose'],

            ['name' => 'Purple',       'code' => 'purple'],
            ['name' => 'Lavender',     'code' => 'lavender'],

            ['name' => 'Green',        'code' => 'green'],
            ['name' => 'Olive',        'code' => 'olive'],
            ['name' => 'Forest Green', 'code' => 'forest-green'],
            ['name' => 'Mint',         'code' => 'mint'],

            ['name' => 'Yellow',       'code' => 'yellow'],
            ['name' => 'Mustard',      'code' => 'mustard'],
            ['name' => 'Orange',       'code' => 'orange'],
            ['name' => 'Rust',         'code' => 'rust'],
        ];

        foreach ($colors as $color) {
            Color::updateOrCreate(
                ['code' => $color['code']],
                ['name' => $color['name']]
            );
        }
    }
}
