<?php

namespace Database\Seeders;

use App\Models\Banner;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ១. រៀបចំ Data ជាមួយ Local Path ឱ្យច្បាស់លាស់
        $bannersData = [
            [
                'menu_id'     => 1,
                'title'       => 'WOMEN',
                'description' => 'Women collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'local_image' => 'C:\Users\MSI I5\Downloads\b1.jpg',
                'is_active'   => true,
            ],
            [
                'menu_id'     => 2,
                'title'       => 'MEN',
                'description' => 'Men collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'local_image' => 'C:\Users\MSI I5\Downloads\b2.jpg',
                'is_active'   => true,
            ],
            [
                'menu_id'     => 3,
                'title'       => 'KIDS',
                'description' => 'Kids collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'local_image' => 'C:\Users\MSI I5\Downloads\b3.jpg',
                'is_active'   => true,
            ],
            [
                'menu_id'     => 4,
                'title'       => 'SPORT',
                'description' => 'Sport collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'local_image' => 'C:\Users\MSI I5\Downloads\b4.webp',
                'is_active'   => true,
            ],
            [
                'menu_id'     => 5,
                'title'       => 'SALE',
                'description' => 'Seasonal savings on your favourite styles. Modern style meets unbeatable prices. Limited time while stocks last.',
                'local_image' => 'C:\Users\MSI I5\Downloads\b5.jpg',
                'is_active'   => true,
            ],
            [
                'menu_id'     => null,
                'title'       => 'Move Different.',
                'description' => 'Premium shoes, clothing, accessories and sport gear engineered for those who lead. Built to move. Designed to stand out.',
                'local_image' => 'C:\Users\MSI I5\Downloads\b0.webp',
                'is_active'   => true,
            ],
        ];

        // ២. Loop Upload ទៅ Cloudinary និង Save ចូល Database
        foreach ($bannersData as $data) {
            $uploadedImage = Cloudinary::upload($data['local_image'], [
                'folder' => 'banner_images'
            ]);

            Banner::create([
                'menu_id'     => $data['menu_id'],
                'title'       => $data['title'],
                'description' => $data['description'],
                'image_path'  => $uploadedImage->getSecurePath(),
                'is_active'   => $data['is_active'],
            ]);
        }
    }
}
