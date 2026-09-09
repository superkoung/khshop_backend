<?php

namespace Database\Seeders;

use App\Models\Banner;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $imgB0 = Cloudinary::upload('C:\Users\MSI I5\Downloads\b0.webp', ['folder' => 'banner_images' ]);
        $imgB1 = Cloudinary::upload('C:\Users\MSI I5\Downloads\b1.jpg', ['folder' => 'banner_images' ]);
        $imgB2 = Cloudinary::upload('C:\Users\MSI I5\Downloads\b2.jpg', ['folder' => 'banner_images' ]);
        $imgB3 = Cloudinary::upload('C:\Users\MSI I5\Downloads\b3.jpg', ['folder' => 'banner_images' ]);
        $imgB4 = Cloudinary::upload('C:\Users\MSI I5\Downloads\b4.webp', ['folder' => 'banner_images' ]);
        $imgB5 = Cloudinary::upload('C:\Users\MSI I5\Downloads\b5.jpg', ['folder' => 'banner_images' ]);
        $banners = [
            [
                'menu_id'     => 1,
                'title'       => 'WOMEN',
                'description' => 'Women collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'image_path'  => $imgB1->getSecurePath(),
                'is_active'   => true,
            ],
            [
                'menu_id'     => 2,
                'title'       => 'MEN',
                'description' => 'Men collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'image_path'  => $imgB2->getSecurePath(),
                'is_active'   => true,
            ],
            [
                'menu_id'     => 3,
                'title'       => 'KIDS',
                'description' => 'Kids collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'image_path'  => $imgB3->getSecurePath(),
                'is_active'   => true,
            ],
            [
                'menu_id'     => 4,
                'title'       => 'SPORT',
                'description' => 'Sport collection at KhShop. Modern style meets unbeatable prices for men and women. Premium quality, modern style, limited time only.',
                'image_path'  => $imgB4->getSecurePath(),
                'is_active'   => true,
            ],
            [
                'menu_id'     => 5,
                'title'       => 'SALE',
                'description' => 'Seasonal savings on your favourite styles. Modern style meets unbeatable prices. Limited time while stocks last.',
                'image_path'  => $imgB5->getSecurePath(),
                'is_active'   => true,
            ],
            [
                'menu_id'     => null,
                'title'       => 'Move Different.',
                'description' => 'Premium shoes, clothing, accessories and sport gear engineered for those who lead. Built to move. Designed to stand out.',
                'image_path'  => $imgB0->getSecurePath(),
                'is_active'   => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }
    }
}
