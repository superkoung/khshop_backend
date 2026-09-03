<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $women = Category::create([
            'name'        => 'WOMEN',
            'slug'        => 'women',
            'description' => 'Women\'s fashion, footwear, and accessories collection',
            'parent_id'   => null,
        ]);

        $men = Category::create([
            'name'        => 'MEN',
            'slug'        => 'men',
            'description' => 'Men\'s fashion, footwear, and accessories collection',
            'parent_id'   => null,
        ]);

        $kids = Category::create([
            'name'        => 'KIDS',
            'slug'        => 'kids',
            'description' => 'Kids clothing and footwear collection for boys and girls',
            'parent_id'   => null,
        ]);

        $sport = Category::create([
            'name'        => 'SPORT',
            'slug'        => 'sport',
            'description' => 'Sportswear, athletic shoes, and equipment',
            'parent_id'   => null,
        ]);

        $sales = Category::create([
            'name'        => 'SALE / PROMOTION',
            'slug'        => 'sale',
            'description' => 'Special discount items and promotional products',
            'parent_id'   => null,
        ]);

        $womenChildren = [
            [
                'name'        => 'Shirt',
                'slug'        => 'shirt-women',
                'description' => 'Women\'s shirts, tops, and t-shirts collection',
            ],
            [
                'name'        => 'Shoes',
                'slug'        => 'shoes-women',
                'description' => 'Women\'s sneakers, heels, and footwear',
            ],
            [
                'name'        => 'Clothing',
                'slug'        => 'clothing-women',
                'description' => 'Women\'s dresses, skirts, and apparel',
            ],
            [
                'name'        => 'Accessories',
                'slug'        => 'accessories-women',
                'description' => 'Women\'s bags, jewelry, and accessories',
            ],
            [
                'name'        => 'Sport',
                'slug'        => 'sport-women',
                'description' => '',
            ],
        ];

        foreach ($womenChildren as $child) {
            Category::create([
                'name'        => $child['name'],
                'slug'        => $child['slug'],
                'description' => $child['description'],
                'parent_id'   => $women->id,
            ]);
        }

        $menChildren = [
            [
                'name'        => 'Shirt',
                'slug'        => 'shirt-men',
                'description' => 'Men\'s shirts, t-shirts, and polos',
            ],
            [
                'name'        => 'Shoes',
                'slug'        => 'shoes-men',
                'description' => 'Men\'s sneakers, athletic, and formal shoes',
            ],
            [
                'name'        => 'Clothing',
                'slug'        => 'clothing-men',
                'description' => 'Men\'s pants, hoodies, and jackets',
            ],
            [
                'name'        => 'Accessories',
                'slug'        => 'accessories-men',
                'description' => 'Men\'s caps, belts, and bags',
            ],
            [
                'name'        => 'Sport',
                'slug'        => 'sport-men',
                'description' => '',
            ],
        ];

        foreach ($menChildren as $child) {
            Category::create([
                'name'        => $child['name'],
                'slug'        => $child['slug'],
                'description' => $child['description'],
                'parent_id'   => $men->id,
            ]);
        }

        $kidsChildren = [
            [
                'name'        => 'Girl Shoes',
                'slug'        => 'girl-shoes',
                'description' => 'Shoes and footwear for girls',
            ],
            [
                'name'        => 'Boy Shoes',
                'slug'        => 'boy-shoes',
                'description' => 'Shoes and footwear for boys',
            ],
            [
                'name'        => 'Girl Clothing',
                'slug'        => 'girl-clothing',
                'description' => 'Dresses, shirts, and clothing for girls',
            ],
            [
                'name'        => 'Boy Clothing',
                'slug'        => 'boy-clothing',
                'description' => 'Shirts, pants, and clothing for boys',
            ],
            [
                'name'        => 'Kids Accessories',
                'slug'        => 'kids-accessories',
                'description' => 'Bags, hats, and accessories for kids',
            ],
        ];

        foreach ($kidsChildren as $child) {
            Category::create([
                'name'        => $child['name'],
                'slug'        => $child['slug'],
                'description' => $child['description'],
                'parent_id'   => $kids->id,
            ]);
        }

        $sportChildren = [
            [
                'name'        => 'Running',
                'slug'        => 'running-sport',
                'description' => 'Running shoes and apparel',
            ],
            [
                'name'        => 'Football',
                'slug'        => 'football-sport',
                'description' => 'Football boots, jerseys, and gear',
            ],
            [
                'name'        => 'Training',
                'slug'        => 'training-sport',
                'description' => 'Gym and training gear',
            ],
            [
                'name'        => 'Volleyball',
                'slug'        => 'volleyball-sport',
                'description' => 'volleyball boots, jerseys, and gear',
            ],
        ];

        foreach ($sportChildren as $child) {
            Category::create([
                'name'        => $child['name'],
                'slug'        => $child['slug'],
                'description' => $child['description'],
                'parent_id'   => $sport->id,
            ]);
        }

        $saleChildren = [
            [
                'name'        => 'Women',
                'slug'        => 'women-sale',
                'description' => '',
            ],
            [
                'name'        => 'Men',
                'slug'        => 'men-sale',
                'description' => '',
            ],
            [
                'name'        => 'Boy kids',
                'slug'        => 'boy-kids-sale',
                'description' => '',
            ],
            [
                'name'        => 'Girl kids',
                'slug'        => 'girl-kids-sale',
                'description' => '',
            ],
            [
                'name'        => 'Men sport',
                'slug'        => 'men-sport-sale',
                'description' => '',
            ],
            [
                'name'        => 'Women sport',
                'slug'        => 'women-sport-sale',
                'description' => '',
            ],
        ];
        foreach ($saleChildren as $sale) {
            Category::create([
                'name'        => $sale['name'],
                'slug'        => $sale['slug'],
                'description' => $sale['description'],
                'parent_id'   => $sales->id,
            ]);
        }

    }
}
