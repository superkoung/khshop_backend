<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 15 subcategories × 4 products = 60 products
     */
    public function run(): void
    {
        $products = [

            /*
            |--------------------------------------------------------------------------
            | WOMEN - SHOES
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'shoes-women',
                'brand' => 'nike',
                'name' => "Nike Air Force 1 '07",
                'description' => "Classic women's lifestyle sneakers with a clean low-top design.",
                'price' => 120,
            ],
            [
                'category' => 'shoes-women',
                'brand' => 'adidas',
                'name' => 'adidas Samba OG Shoes',
                'description' => "Classic low-profile lifestyle shoes inspired by adidas football heritage.",
                'price' => 100,
            ],
            [
                'category' => 'shoes-women',
                'brand' => 'new-balance',
                'name' => 'New Balance 530',
                'description' => "Retro-inspired everyday sneakers with cushioned comfort.",
                'price' => 100,
            ],
            [
                'category' => 'shoes-women',
                'brand' => 'asics',
                'name' => 'ASICS GEL-KAYANO 31',
                'description' => "Stability running shoes designed for comfortable everyday running.",
                'price' => 165,
            ],

            /*
            |--------------------------------------------------------------------------
            | WOMEN - CLOTHING
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'clothing-women',
                'brand' => 'nike',
                'name' => 'Nike Sportswear Phoenix Fleece Hoodie',
                'description' => "Soft fleece hoodie with a relaxed everyday fit.",
                'price' => 70,
            ],
            [
                'category' => 'clothing-women',
                'brand' => 'adidas',
                'name' => 'adidas Essentials 3-Stripes T-Shirt',
                'description' => "Classic cotton T-shirt featuring the signature 3-Stripes style.",
                'price' => 35,
            ],
            [
                'category' => 'clothing-women',
                'brand' => 'uniqlo',
                'name' => 'Uniqlo AIRism Cotton T-Shirt',
                'description' => "Everyday T-shirt combining a cotton-like feel with AIRism comfort.",
                'price' => 25,
            ],
            [
                'category' => 'clothing-women',
                'brand' => 'zara',
                'name' => 'Zara Oversized Blazer',
                'description' => "Modern oversized blazer designed for contemporary styling.",
                'price' => 90,
            ],

            /*
            |--------------------------------------------------------------------------
            | WOMEN - ACCESSORIES
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'accessories-women',
                'brand' => 'nike',
                'name' => 'Nike Heritage Crossbody Bag',
                'description' => "Compact everyday crossbody bag with practical storage.",
                'price' => 35,
            ],
            [
                'category' => 'accessories-women',
                'brand' => 'adidas',
                'name' => 'adidas Adicolor Classic Backpack',
                'description' => "Everyday backpack featuring classic adidas styling.",
                'price' => 45,
            ],
            [
                'category' => 'accessories-women',
                'brand' => 'new-balance',
                'name' => 'New Balance Classic NB Cap',
                'description' => "Classic casual cap featuring New Balance branding.",
                'price' => 30,
            ],
            [
                'category' => 'accessories-women',
                'brand' => 'calvin-klein',
                'name' => 'Calvin Klein Logo Crossbody Bag',
                'description' => "Minimal crossbody bag with signature Calvin Klein branding.",
                'price' => 100,
            ],

            /*
            |--------------------------------------------------------------------------
            | WOMEN - SPORT
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'sport-women',
                'brand' => 'nike',
                'name' => 'Nike Zenvy Gentle-Support Bra',
                'description' => "Soft training sports bra designed for comfortable movement.",
                'price' => 60,
            ],
            [
                'category' => 'sport-women',
                'brand' => 'adidas',
                'name' => 'adidas Own the Run T-Shirt',
                'description' => "Lightweight running T-shirt designed for everyday training.",
                'price' => 45,
            ],
            [
                'category' => 'sport-women',
                'brand' => 'puma',
                'name' => 'PUMA Train All Day Leggings',
                'description' => "Training leggings designed for gym and active workouts.",
                'price' => 55,
            ],
            [
                'category' => 'sport-women',
                'brand' => 'hoka',
                'name' => 'HOKA Mach 6',
                'description' => "Responsive running shoes designed for fast everyday training.",
                'price' => 140,
            ],

            /*
            |--------------------------------------------------------------------------
            | MEN - SHOES
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'shoes-men',
                'brand' => 'nike',
                'name' => "Nike Air Force 1 '07 Men's",
                'description' => "Classic men's lifestyle sneakers with the iconic Air Force 1 design.",
                'price' => 120,
            ],
            [
                'category' => 'shoes-men',
                'brand' => 'adidas',
                'name' => 'adidas Gazelle Shoes',
                'description' => "Classic low-profile lifestyle sneakers with a retro design.",
                'price' => 100,
            ],
            [
                'category' => 'shoes-men',
                'brand' => 'new-balance',
                'name' => 'New Balance 574',
                'description' => "Classic everyday sneakers with a retro running-inspired design.",
                'price' => 90,
            ],
            [
                'category' => 'shoes-men',
                'brand' => 'jordan',
                'name' => 'Air Jordan 1 Low',
                'description' => "Low-top basketball-inspired lifestyle sneakers.",
                'price' => 120,
            ],

            /*
            |--------------------------------------------------------------------------
            | MEN - CLOTHING
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'clothing-men',
                'brand' => 'nike',
                'name' => 'Nike Sportswear Club Fleece Hoodie',
                'description' => "Classic fleece hoodie designed for everyday casual wear.",
                'price' => 65,
            ],
            [
                'category' => 'clothing-men',
                'brand' => 'adidas',
                'name' => 'adidas Essentials 3-Stripes Hoodie',
                'description' => "Classic hoodie featuring adidas 3-Stripes styling.",
                'price' => 70,
            ],
            [
                'category' => 'clothing-men',
                'brand' => 'levi',
                'name' => "Levi's 511 Slim Jeans",
                'description' => "Slim-fit jeans with a streamlined silhouette.",
                'price' => 100,
            ],
            [
                'category' => 'clothing-men',
                'brand' => 'lacoste',
                'name' => 'Lacoste Classic Fit Polo Shirt',
                'description' => "Classic cotton polo shirt with the iconic crocodile logo.",
                'price' => 110,
            ],

            /*
            |--------------------------------------------------------------------------
            | MEN - ACCESSORIES
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'accessories-men',
                'brand' => 'nike',
                'name' => 'Nike Heritage86 Cap',
                'description' => "Classic six-panel sports cap for everyday wear.",
                'price' => 28,
            ],
            [
                'category' => 'accessories-men',
                'brand' => 'adidas',
                'name' => 'adidas Trefoil Baseball Cap',
                'description' => "Classic baseball cap featuring the Trefoil logo.",
                'price' => 30,
            ],
            [
                'category' => 'accessories-men',
                'brand' => 'vans',
                'name' => 'Vans Old Skool Backpack',
                'description' => "Classic casual backpack for everyday carrying.",
                'price' => 50,
            ],
            [
                'category' => 'accessories-men',
                'brand' => 'the-north-face',
                'name' => 'The North Face Borealis Backpack',
                'description' => "Versatile backpack designed for everyday and outdoor use.",
                'price' => 110,
            ],

            /*
            |--------------------------------------------------------------------------
            | MEN - SPORT
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'sport-men',
                'brand' => 'nike',
                'name' => 'Nike Dri-FIT Academy Football Jersey',
                'description' => "Moisture-managing football jersey designed for training.",
                'price' => 45,
            ],
            [
                'category' => 'sport-men',
                'brand' => 'adidas',
                'name' => 'adidas Own the Run T-Shirt',
                'description' => "Lightweight running T-shirt for everyday training.",
                'price' => 45,
            ],
            [
                'category' => 'sport-men',
                'brand' => 'puma',
                'name' => 'PUMA Training Shorts',
                'description' => "Lightweight training shorts for gym and sports activities.",
                'price' => 40,
            ],
            [
                'category' => 'sport-men',
                'brand' => 'jordan',
                'name' => 'Jordan Dri-FIT Sport Shorts',
                'description' => "Performance shorts designed for basketball and training.",
                'price' => 55,
            ],

            /*
            |--------------------------------------------------------------------------
            | KIDS - GIRL SHOES
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'girl-shoes',
                'brand' => 'nike',
                'name' => 'Nike Dunk Low Older Kids',
                'description' => "Classic low-top sneakers designed for older kids.",
                'price' => 100,
            ],
            [
                'category' => 'girl-shoes',
                'brand' => 'adidas',
                'name' => 'adidas Grand Court 2.0 Kids',
                'description' => "Classic everyday sneakers for kids.",
                'price' => 60,
            ],
            [
                'category' => 'girl-shoes',
                'brand' => 'new-balance',
                'name' => 'New Balance 574 Kids',
                'description' => "Classic kids sneakers inspired by the original 574.",
                'price' => 65,
            ],
            [
                'category' => 'girl-shoes',
                'brand' => 'jordan',
                'name' => 'Jordan 1 Low Older Kids',
                'description' => "Low-top basketball-inspired sneakers for older kids.",
                'price' => 110,
            ],

            /*
            |--------------------------------------------------------------------------
            | KIDS - BOY SHOES
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'boy-shoes',
                'brand' => 'nike',
                'name' => 'Nike Air Max 1 Older Kids',
                'description' => "Classic Air Max lifestyle sneakers for older kids.",
                'price' => 110,
            ],
            [
                'category' => 'boy-shoes',
                'brand' => 'adidas',
                'name' => 'adidas Superstar Shoes Kids',
                'description' => "Classic shell-toe sneakers designed for kids.",
                'price' => 70,
            ],
            [
                'category' => 'boy-shoes',
                'brand' => 'new-balance',
                'name' => 'New Balance 574 Core Kids',
                'description' => "Classic everyday sneakers with a comfortable fit.",
                'price' => 65,
            ],
            [
                'category' => 'boy-shoes',
                'brand' => 'jordan',
                'name' => 'Jordan 1 Mid Older Kids',
                'description' => "Basketball-inspired high-top sneakers for older kids.",
                'price' => 130,
            ],

            /*
            |--------------------------------------------------------------------------
            | KIDS - GIRL CLOTHING
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'girl-clothing',
                'brand' => 'nike',
                'name' => 'Nike Sportswear Club Fleece Hoodie Kids',
                'description' => "Soft fleece hoodie designed for everyday kids wear.",
                'price' => 50,
            ],
            [
                'category' => 'girl-clothing',
                'brand' => 'adidas',
                'name' => 'adidas Essentials 3-Stripes T-Shirt Kids',
                'description' => "Classic everyday T-shirt for kids.",
                'price' => 30,
            ],
            [
                'category' => 'girl-clothing',
                'brand' => 'uniqlo',
                'name' => 'Uniqlo Kids AIRism Cotton T-Shirt',
                'description' => "Comfortable everyday kids T-shirt.",
                'price' => 20,
            ],
            [
                'category' => 'girl-clothing',
                'brand' => 'zara',
                'name' => 'Zara Kids Printed Dress',
                'description' => "Casual printed dress designed for girls.",
                'price' => 45,
            ],

            /*
            |--------------------------------------------------------------------------
            | KIDS - BOY CLOTHING
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'boy-clothing',
                'brand' => 'nike',
                'name' => 'Nike Sportswear Club Fleece Crew Kids',
                'description' => "Soft fleece crewneck designed for everyday kids wear.",
                'price' => 45,
            ],
            [
                'category' => 'boy-clothing',
                'brand' => 'adidas',
                'name' => 'adidas Essentials 3-Stripes Hoodie Kids',
                'description' => "Classic hoodie with adidas 3-Stripes styling.",
                'price' => 50,
            ],
            [
                'category' => 'boy-clothing',
                'brand' => 'levi',
                'name' => "Levi's Kids 511 Slim Jeans",
                'description' => "Slim-fit jeans designed for kids.",
                'price' => 55,
            ],
            [
                'category' => 'boy-clothing',
                'brand' => 'lacoste',
                'name' => 'Lacoste Kids Polo Shirt',
                'description' => "Classic polo shirt for boys.",
                'price' => 75,
            ],

            /*
            |--------------------------------------------------------------------------
            | KIDS - ACCESSORIES
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'kids-accessories',
                'brand' => 'nike',
                'name' => 'Nike Brasilia Kids Backpack',
                'description' => "Compact everyday backpack designed for kids.",
                'price' => 40,
            ],
            [
                'category' => 'kids-accessories',
                'brand' => 'adidas',
                'name' => 'adidas Classic Kids Backpack',
                'description' => "Everyday kids backpack with classic adidas styling.",
                'price' => 40,
            ],
            [
                'category' => 'kids-accessories',
                'brand' => 'vans',
                'name' => 'Vans Kids Old Skool Backpack',
                'description' => "Classic backpack inspired by Vans skate style.",
                'price' => 45,
            ],
            [
                'category' => 'kids-accessories',
                'brand' => 'under-armour',
                'name' => 'Under Armour Scrimmage Backpack',
                'description' => "Durable backpack suitable for school and sports.",
                'price' => 45,
            ],

            /*
            |--------------------------------------------------------------------------
            | SPORT - RUNNING
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'running-sport',
                'brand' => 'nike',
                'name' => 'Nike Pegasus 41',
                'description' => "Everyday road running shoes designed for responsive cushioning.",
                'price' => 140,
            ],
            [
                'category' => 'running-sport',
                'brand' => 'adidas',
                'name' => 'adidas Ultraboost 5',
                'description' => "Responsive running shoes designed for everyday road running.",
                'price' => 180,
            ],
            [
                'category' => 'running-sport',
                'brand' => 'asics',
                'name' => 'ASICS GEL-NIMBUS 27',
                'description' => "Highly cushioned running shoes designed for daily road running.",
                'price' => 170,
            ],
            [
                'category' => 'running-sport',
                'brand' => 'hoka',
                'name' => 'HOKA Clifton 10',
                'description' => "Lightweight cushioned running shoes for everyday miles.",
                'price' => 150,
            ],

            /*
            |--------------------------------------------------------------------------
            | SPORT - FOOTBALL
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'football-sport',
                'brand' => 'nike',
                'name' => 'Nike Mercurial Vapor 16 Academy',
                'description' => "Football boots designed for speed and quick movement.",
                'price' => 95,
            ],
            [
                'category' => 'football-sport',
                'brand' => 'adidas',
                'name' => 'adidas F50 League',
                'description' => "Lightweight football boots designed for speed.",
                'price' => 100,
            ],
            [
                'category' => 'football-sport',
                'brand' => 'puma',
                'name' => 'PUMA FUTURE 8 Play',
                'description' => "Football boots designed for agile play and control.",
                'price' => 90,
            ],
            [
                'category' => 'football-sport',
                'brand' => 'adidas',
                'name' => 'adidas Tiro League Ball',
                'description' => "Training football designed for regular practice.",
                'price' => 35,
            ],

            /*
            |--------------------------------------------------------------------------
            | SPORT - TRAINING
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'training-sport',
                'brand' => 'nike',
                'name' => 'Nike Metcon 10',
                'description' => "Training shoes designed for strength and gym workouts.",
                'price' => 150,
            ],
            [
                'category' => 'training-sport',
                'brand' => 'adidas',
                'name' => 'adidas Dropset 3 Trainer',
                'description' => "Training shoes designed for strength and gym workouts.",
                'price' => 130,
            ],
            [
                'category' => 'training-sport',
                'brand' => 'reebok',
                'name' => 'Reebok Nano X5',
                'description' => "Cross-training shoes designed for gym workouts.",
                'price' => 140,
            ],
            [
                'category' => 'training-sport',
                'brand' => 'under-armour',
                'name' => 'Under Armour Tech 2.0 T-Shirt',
                'description' => "Lightweight training shirt with quick-drying fabric.",
                'price' => 35,
            ],

            /*
            |--------------------------------------------------------------------------
            | SPORT - VOLLEYBALL
            |--------------------------------------------------------------------------
            */

            [
                'category' => 'volleyball-sport',
                'brand' => 'asics',
                'name' => 'ASICS GEL-ROCKET 11',
                'description' => "Indoor court shoes designed for volleyball and court sports.",
                'price' => 85,
            ],
            [
                'category' => 'volleyball-sport',
                'brand' => 'mizuno',
                'name' => 'Mizuno Wave Lightning Z8',
                'description' => "Indoor court shoes designed for volleyball performance.",
                'price' => 150,
            ],
            [
                'category' => 'volleyball-sport',
                'brand' => 'adidas',
                'name' => 'adidas Crazyflight 6',
                'description' => "Lightweight indoor court shoes for quick movement.",
                'price' => 120,
            ],
            [
                'category' => 'volleyball-sport',
                'brand' => 'nike',
                'name' => 'Nike Zoom Hyperset 2',
                'description' => "Indoor court shoes designed for volleyball movement.",
                'price' => 140,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create / Update Products
        |--------------------------------------------------------------------------
        */

        $createdProducts = [];

        foreach ($products as $item) {

            $brand = Brand::where('slug', $item['brand'])->first();

            if (!$brand) {
                $this->command->warn(
                    "Brand not found: {$item['brand']} - {$item['name']}"
                );

                continue;
            }

            $category = Category::where('slug', $item['category'])
                ->whereNotNull('parent_id')
                ->first();

            if (!$category) {
                $this->command->warn(
                    "Category not found: {$item['category']} - {$item['name']}"
                );

                continue;
            }

            $slug = Str::slug($item['name']);

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'slug' => $slug,
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'discount_type' => null,
                    'discount_value' => 0,
                    'sale_price' => null,
                    'is_active' => true,
                ]
            );

            $createdProducts[] = $product;
        }

        /*
        |--------------------------------------------------------------------------
        | Apply Discount
        |--------------------------------------------------------------------------
        |
        | Exactly 30% of successfully created/updated products
        | receive a discount between 10% and 40%.
        |
        */

        $totalProducts = count($createdProducts);

        $discountCount = (int) floor($totalProducts * 0.30);

        mt_srand(20260906);

        $discountProducts = collect($createdProducts)
            ->shuffle()
            ->take($discountCount);

        foreach ($discountProducts as $product) {

            $discount = mt_rand(10, 40);

            $salePrice = round(
                $product->price * (1 - ($discount / 100)),
                2
            );

            $product->update([
                'discount_type' => 'percent',
                'discount_value' => $discount,
                'sale_price' => $salePrice,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Reset Remaining Products
        |--------------------------------------------------------------------------
        */

        $discountedIds = $discountProducts
            ->pluck('id')
            ->toArray();

        Product::whereIn(
            'id',
            collect($createdProducts)->pluck('id')
        )
            ->whereNotIn('id', $discountedIds)
            ->update([
                'discount_type' => null,
                'discount_value' => 0,
                'sale_price' => null,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Seeder Summary
        |--------------------------------------------------------------------------
        */

        $this->command->info(
            "Product seeding completed: {$totalProducts} products created/updated."
        );

        $this->command->info(
            "Expected catalog size: 15 subcategories × 4 products = 60 products."
        );

        $this->command->info(
            "Discounted products: {$discountCount} (" .
            (
                $totalProducts > 0
                    ? round(($discountCount / $totalProducts) * 100, 1)
                    : 0
            ) .
            "%)"
        );
    }
}

