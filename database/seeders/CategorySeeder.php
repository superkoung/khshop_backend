<?php

namespace Database\Seeders;

use App\Models\Category;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. បង្កើត Parent Categories (គ្មានរូបភាព)
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

        // 2. Women Sub-categories
        $womenChildren = [
            [
                'name'        => 'Shoes',
                'slug'        => 'shoes-women',
                'description' => 'Women\'s sneakers, heels, and footwear',
                'file_name'   => 'shoes_women.jpg' // បន្ថែម Extension (.jpg / .png / .webp)
            ],
            [
                'name'        => 'Clothing',
                'slug'        => 'clothing-women',
                'description' => 'Women\'s dresses, skirts, and apparel',
                'file_name'   => 'clothing_women.jpg'
            ],
            [
                'name'        => 'Accessories',
                'slug'        => 'accessories-women',
                'description' => 'Women\'s bags, jewelry, and accessories',
                'file_name'   => 'accessory_women.jpg'
            ],
            [
                'name'        => 'Sport',
                'slug'        => 'sport-women',
                'description' => 'Women\'s activewear and sport accessories',
                'file_name'   => 'sport_women.jpg'
            ],
        ];

        $this->createSubCategories($womenChildren, $women->id);

        // 3. Men Sub-categories
        $menChildren = [
            [
                'name'        => 'Shoes',
                'slug'        => 'shoes-men',
                'description' => 'Men\'s sneakers, athletic, and formal shoes',
                'file_name'   => 'shoes_men.jpg'
            ],
            [
                'name'        => 'Clothing',
                'slug'        => 'clothing-men',
                'description' => 'Men\'s pants, hoodies, and jackets',
                'file_name'   => 'clothing_men.jpg'
            ],
            [
                'name'        => 'Accessories',
                'slug'        => 'accessories-men',
                'description' => 'Men\'s caps, belts, and bags',
                'file_name'   => 'accessory_men.jpg'
            ],
            [
                'name'        => 'Sport',
                'slug'        => 'sport-men',
                'description' => 'Men\'s activewear and training gear',
                'file_name'   => 'sport_men.jpg'
            ],
        ];

        $this->createSubCategories($menChildren, $men->id);

        // 4. Kids Sub-categories
        $kidsChildren = [
            [
                'name'        => 'Girl Shoes',
                'slug'        => 'girl-shoes',
                'description' => 'Shoes and footwear for girls',
                'file_name'   => 'shoes_girl_kid.jpg'
            ],
            [
                'name'        => 'Boy Shoes',
                'slug'        => 'boy-shoes',
                'description' => 'Shoes and footwear for boys',
                'file_name'   => 'shoes_boy_kid.jpg'
            ],
            [
                'name'        => 'Girl Clothing',
                'slug'        => 'girl-clothing',
                'description' => 'Dresses, shirts, and clothing for girls',
                'file_name'   => 'clothing_girl_kid.jpg'
            ],
            [
                'name'        => 'Boy Clothing',
                'slug'        => 'boy-clothing',
                'description' => 'Shirts, pants, and clothing for boys',
                'file_name'   => 'clothing_boy_kid.jpg'
            ],
            [
                'name'        => 'Kids Accessories',
                'slug'        => 'kids-accessories',
                'description' => 'Bags, hats, and accessories for kids',
                'file_name'   => 'accessory_kids.jpg'
            ],
        ];

        $this->createSubCategories($kidsChildren, $kids->id);

        // 5. Sport Sub-categories
        $sportChildren = [
            [
                'name'        => 'Running',
                'slug'        => 'running-sport',
                'description' => 'Running shoes and apparel',
                'file_name'   => 'running.jpg'
            ],
            [
                'name'        => 'Football',
                'slug'        => 'football-sport',
                'description' => 'Football boots, jerseys, and gear',
                'file_name'   => 'football.jpg'
            ],
            [
                'name'        => 'Training',
                'slug'        => 'training-sport',
                'description' => 'Gym and workout equipment',
                'file_name'   => 'training.jpg' // បន្ថែម file_name ដែលបាត់
            ],
            [
                'name'        => 'Volleyball',
                'slug'        => 'volleyball-sport',
                'description' => 'Volleyball boots, jerseys, and gear',
                'file_name'   => 'volleyball.jpg'
            ],
        ];

        $this->createSubCategories($sportChildren, $sport->id);

        // 6. Sale Sub-categories (គ្មានរូបភាព)
        $saleChildren = [
            ['name' => 'Women', 'slug' => 'women-sale', 'description' => 'Women discounted items'],
            ['name' => 'Men', 'slug' => 'men-sale', 'description' => 'Men discounted items'],
            ['name' => 'Boy kids', 'slug' => 'boy-kids-sale', 'description' => 'Boy kids discounted items'],
            ['name' => 'Girl kids', 'slug' => 'girl-kids-sale', 'description' => 'Girl kids discounted items'],
            ['name' => 'Men sport', 'slug' => 'men-sport-sale', 'description' => 'Men sport discounted items'],
            ['name' => 'Women sport', 'slug' => 'women-sport-sale', 'description' => 'Women sport discounted items'],
        ];

        foreach ($saleChildren as $sale) {
            Category::create([
                'name'        => $sale['name'],
                'slug'        => $sale['slug'],
                'description' => $sale['description'],
                'parent_id'   => $sales->id,
                'image_path'  => null,
            ]);
        }
    }

    /**
     * Helper Method សម្រាប់ Upload រូបភាព និង create Sub Categories ឱ្យមានសុវត្ថិភាព
     */
    private function createSubCategories(array $children, int $parentId): void
    {
        foreach ($children as $child) {
            $imagePath = null;

            if (isset($child['file_name'])) {
                $fullLocalPath = 'C:\Users\MSI I5\Downloads\\' . $child['file_name'];

                // ឆែកមើលថាតើ File នោះពិតជាមាននៅក្នុង Downloads ឬអត់
                if (file_exists($fullLocalPath)) {
                    $uploaded = Cloudinary::upload($fullLocalPath, [
                        'folder' => 'Category_images'
                    ]);
                    $imagePath = $uploaded->getSecurePath();
                }
            }

            Category::create([
                'name'        => $child['name'],
                'slug'        => $child['slug'],
                'description' => $child['description'],
                'parent_id'   => $parentId,
                'image_path'  => $imagePath,
            ]);
        }
    }
}
