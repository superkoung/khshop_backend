<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariantSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $products = DB::table('products')
            ->select('id', 'name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Colors
        |--------------------------------------------------------------------------
        */

        $colors = DB::table('colors')
            ->select('id', 'name')
            ->get()
            ->keyBy(fn ($color) => strtolower(trim($color->name)));

        /*
        |--------------------------------------------------------------------------
        | Sizes
        |--------------------------------------------------------------------------
        */

        $sizes = DB::table('sizes')
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        // Adult
        $adultClothingSizes = $sizes
            ->whereBetween('id', [1, 7])
            ->keyBy(fn ($size) => strtoupper($size->name));

        $adultPantsSizes = $sizes
            ->whereBetween('id', [8, 16])
            ->keyBy(fn ($size) => $size->name);

        $adultShoeSizes = $sizes
            ->whereBetween('id', [17, 22])
            ->keyBy(fn ($size) => $size->name);

        // Kids
        $kidsClothingSizes = $sizes
            ->whereBetween('id', [23, 29])
            ->keyBy(fn ($size) => strtoupper($size->name));

        $kidsPantsSizes = $sizes
            ->whereBetween('id', [30, 38])
            ->keyBy(fn ($size) => $size->name);

        $kidsShoeSizes = $sizes
            ->whereBetween('id', [39, 44])
            ->keyBy(fn ($size) => $size->name);

        /*
        |--------------------------------------------------------------------------
        | IMAGE DATA
        |--------------------------------------------------------------------------
        |
        | image_id => image slug
        |
        */

        $imageData = [
            1   => 'NIKE-AIR-FORCE-1-07-black',
            2   => 'NIKE-AIR-FORCE-1-07-red',
            3   => 'ADIDAS-SAMBA-OG-SHOES-brown',
            4   => 'ADIDAS-SAMBA-OG-SHOES-red',
            5   => 'NEW-BALANCE-530-Beige',
            6   => 'NEW-BALANCE-530-red',
            7   => 'ASICS-GEL-KAYANO-grey',
            8   => 'NIKE-SPORTSWEAR-PHOENIX-FLEECE-HOODIE-green',
            9   => 'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-Beige',
            10  => 'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-Charcoal',
            11  => 'UNIQLO-AIRISM-COTTON-T-SHIRT-grey',
            12  => 'UNIQLO-AIRISM-COTTON-T-SHIRT-blue',
            13  => 'ZARA-OVERSIZED-BLAZER-grey',
            14  => 'ZARA-OVERSIZED-BLAZER-grey-oliver',
            15  => 'NIKE-HERITAGE-CROSSBODY-BAG-green',
            16  => 'ADIDAS-ADICOLOR-CLASSIC-BACKPACK-blue',
            17  => 'ADIDAS-ADICOLOR-CLASSIC-BACKPACK-oliver',
            18  => 'NEW-BALANCE-CLASSIC-NB-CAP-white',
            19  => 'NEW-BALANCE-CLASSIC-NB-CAP-beige',
            20  => 'CALVIN-KLEIN-LOGO-CROSSBODY-BAG-off-white',
            21  => 'NIKE-ZENVY-GENTLE-SUPPORT-BRA-C7-S10',
            22  => 'ADIDAS-OWN-THE-RUN-T-SHIRT-blue',
            23  => 'PUMA-TRAIN-ALL-DAY-LEGGINGS-white',
            24  => 'PUMA-TRAIN-ALL-DAY-LEGGINGS-brown',
            25  => 'HOKA-MACH-6-chocolate',
            26  => 'HOKA-MACH-6-blue',
            27  => 'NIKE-AIR-FORCE-1-07-MENS-grey',
            28  => 'ADIDAS-GAZELLE-SHOES-off-white',
            29  => 'ADIDAS-GAZELLE-SHOES-beige',
            30  => 'NEW-BALANCE-574-off-white',
            31  => 'NEW-BALANCE-574-red',
            32  => 'AIR-JORDAN-1-LOW-blue',
            33  => 'NIKE-SPORTSWEAR-CLUB-FLEECE-HOODIE-white',
            34  => 'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-black',
            35  => 'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-green',
            36  => 'LEVIS-511-SLIM-JEANS-off-white',
            37  => 'LACOSTE-CLASSIC-FIT-POLO-SHIRT-green',
            38  => 'NIKE-HERITAGE86-CAP-green',
            39  => 'ADIDAS-TREFOIL-BASEBALL-CAP-beige',
            40  => 'ADIDAS-TREFOIL-BASEBALL-CAP-brown',
            41  => 'VANS-OLD-SKOOL-BACKPACK-beige',
            42  => 'VANS-OLD-SKOOL-BACKPACK-grey',
            43  => 'THE-NORTH-FACE-BOREALIS-BACKPACK-navy',
            44  => 'THE-NORTH-FACE-BOREALIS-BACKPACK-green',
            45  => 'NIKE-DRI-FIT-ACADEMY-FOOTBALL-JERSEY-beige',
            46  => 'NIKE-DRI-FIT-ACADEMY-FOOTBALL-JERSEY-Charcoal',
            47  => 'PUMA-TRAINING-SHORTS-Charcoal',
            48  => 'JORDAN-DRI-FIT-SPORT-SHORTS-red',
            49  => 'NIKE-DUNK-LOW-OLDER-KIDS-blue',
            50  => 'ADIDAS-GRAND-COURT-20-KIDS-blue',
            51  => 'NEW-BALANCE-574-KIDS-off-white',
            52  => 'NEW-BALANCE-574-KIDS-green',
            53  => 'JORDAN-1-LOW-OLDER-KIDS-grey',
            54  => 'JORDAN-1-LOW-OLDER-KIDS-olive',
            55  => 'NIKE-AIR-MAX-1-OLDER-KIDS-olive',
            56  => 'ADIDAS-SUPERSTAR-SHOES-KIDS-brown',
            57  => 'ADIDAS-SUPERSTAR-SHOES-KIDS-olive',
            58  => 'NEW-BALANCE-574-CORE-KIDS-beige',
            59  => 'NEW-BALANCE-574-CORE-KIDS-grey',
            60  => 'JORDAN-1-MID-OLDER-KIDS-navy',
            61  => 'NIKE-SPORTSWEAR-CLUB-FLEECE-HOODIE-KIDS-beige',
            62  => 'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-KIDS-white',
            63  => 'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-KIDS-blue',
            64  => 'UNIQLO-KIDS-AIRISM-COTTON-T-SHIRT-beige',
            65  => 'UNIQLO-KIDS-AIRISM-COTTON-T-SHIRT-blue',
            66  => 'ZARA-KIDS-PRINTED-DRESS-brown',
            67  => 'NIKE-SPORTSWEAR-CLUB-FLEECE-CREW-KIDS-white',
            68  => 'NIKE-SPORTSWEAR-CLUB-FLEECE-CREW-KIDS-Charcoal',
            69  => 'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-KIDS-beige',
            70  => 'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-KIDS-brown',
            71  => 'LEVIS-KIDS-511-SLIM-JEANS-beige',
            72  => 'LACOSTE-KIDS-POLO-SHIRT-white',
            73  => 'LACOSTE-KIDS-POLO-SHIRT-Olive',
            74  => 'NIKE-BRASILIA-KIDS-BACKPACK-off-white',
            75  => 'ADIDAS-CLASSIC-KIDS-BACKPACK-black',
            76  => 'ADIDAS-CLASSIC-KIDS-BACKPACK-navy',
            77  => 'VANS-KIDS-OLD-SKOOL-BACKPACK-brown',
            78  => 'VANS-KIDS-OLD-SKOOL-BACKPACK-Charcoal',
            79  => 'UNDER-ARMOUR-SCRIMMAGE-BACKPACK-black',
            80  => 'NIKE-PEGASUS-41-green',
            81  => 'ADIDAS-ULTRABOOST-5-black',
            82  => 'ADIDAS-ULTRABOOST-5-grey',
            83  => 'ASICS-GEL-NIMBUS-27-black',
            84  => 'ASICS-GEL-NIMBUS-27-Charcoal',
            85  => 'HOKA-CLIFTON-10-white',
            86  => 'HOKA-CLIFTON-10-grey',
            87  => 'HOKA-CLIFTON-10-black',
            88  => 'ADIDAS-F50-LEAGUE-black',
            89  => 'PUMA-FUTURE-8-PLAY-off-white',
            90  => 'ADIDAS-TIRO-LEAGUE-BALL-brown',
            91  => 'NIKE-METCON-10-green',
            92  => 'ADIDAS-DROPSET-3-TRAINER-brown',
            93  => 'ADIDAS-DROPSET-3-TRAINER-red',
            94  => 'REEBOK-NANO-X5-navy',
            95  => 'REEBOK-NANO-X5-blue',
            96  => 'UNDER-ARMOUR-TECH-20-T-SHIRT-grey',
            97  => 'UNDER-ARMOUR-TECH-20-T-SHIRT-green',
            98  => 'ASICS-GEL-ROCKET-11-brown',
            99  => 'ASICS-GEL-ROCKET-11-green',
            100 => 'MIZUNO-WAVE-LIGHTNING-Z8-C13-S18',
            101 => 'ADIDAS-CRAZYFLIGHT-6-off-white',
            102 => 'ADIDAS-CRAZYFLIGHT-6-Charcoal',
            103 => 'NIKE-ZOOM-HYPERSET-2-Olive',
        ];

        /*
        |--------------------------------------------------------------------------
        | Color aliases from image slug
        |--------------------------------------------------------------------------
        */

        $colorAliases = [
            'black'      => 'black',
            'white'      => 'white',
            'off-white'  => 'off white',
            'cream'      => 'cream',
            'beige'      => 'beige',
            'camel'      => 'camel',
            'brown'      => 'brown',
            'chocolate'  => 'chocolate',
            'grey'       => 'grey',
            'gray'       => 'grey',
            'charcoal'   => 'charcoal',
            'silver'     => 'silver',
            'navy'       => 'navy',
            'blue'       => 'blue',
            'light-blue' => 'light blue',
            'dark-blue'  => 'dark blue',
            'denim'      => 'denim',
            'red'        => 'red',
            'burgundy'   => 'burgundy',
            'maroon'     => 'maroon',
            'pink'       => 'pink',
            'light-pink' => 'light pink',
            'rose'       => 'rose',
            'purple'     => 'purple',
            'lavender'   => 'lavender',
            'green'      => 'green',
            'olive'      => 'olive',
            'oliver'     => 'olive',
            'forest-green' => 'forest green',
            'mint'       => 'mint',
            'yellow'     => 'yellow',
            'mustard'    => 'mustard',
            'orange'     => 'orange',
            'rust'       => 'rust',
        ];

        /*
        |--------------------------------------------------------------------------
        | Build image map
        |--------------------------------------------------------------------------
        */

        $imageMap = [];

        foreach ($imageData as $imageId => $imageSlug) {

            $slug = strtolower($imageSlug);

            foreach ($colorAliases as $alias => $colorName) {

                $suffix = '-' . $alias;

                if (Str::endsWith($slug, $suffix)) {

                    $productSlug = Str::beforeLast($slug, $suffix);

                    $imageMap[$productSlug][$colorName] = $imageId;

                    break;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Product type
        |--------------------------------------------------------------------------
        */

        $getProductType = function ($name) {

            $name = strtolower($name);

            if (
                str_contains($name, 'kids') &&
                (
                    str_contains($name, 'shoes') ||
                    str_contains($name, 'jordan') ||
                    str_contains($name, 'new balance') ||
                    str_contains($name, 'dunk') ||
                    str_contains($name, 'air max')
                )
            ) {
                return 'kids_shoe';
            }

            if (
                str_contains($name, 'kids') &&
                (
                    str_contains($name, 'jeans') ||
                    str_contains($name, 'pants')
                )
            ) {
                return 'kids_pants';
            }

            if (str_contains($name, 'kids')) {
                return 'kids_clothing';
            }

            if (
                str_contains($name, 'shoes') ||
                str_contains($name, 'shoe') ||
                str_contains($name, 'air force') ||
                str_contains($name, 'samba') ||
                str_contains($name, 'gazelle') ||
                str_contains($name, '574') ||
                str_contains($name, 'pegasus') ||
                str_contains($name, 'ultraboost') ||
                str_contains($name, 'gel-') ||
                str_contains($name, 'mach') ||
                str_contains($name, 'clifton') ||
                str_contains($name, 'f50') ||
                str_contains($name, 'future') ||
                str_contains($name, 'metcon') ||
                str_contains($name, 'dropset') ||
                str_contains($name, 'nano') ||
                str_contains($name, 'rocket') ||
                str_contains($name, 'lightning') ||
                str_contains($name, 'crazyflight') ||
                str_contains($name, 'hyperset') ||
                str_contains($name, 'jordan 1')
            ) {
                return 'shoe';
            }

            if (
                str_contains($name, 'jeans') ||
                str_contains($name, 'shorts') ||
                str_contains($name, 'leggings')
            ) {
                return 'pants';
            }

            if (
                str_contains($name, 'backpack') ||
                str_contains($name, 'crossbody') ||
                str_contains($name, 'cap') ||
                str_contains($name, 'ball')
            ) {
                return 'accessory';
            }

            return 'clothing';
        };

        /*
        |--------------------------------------------------------------------------
        | Get sizes
        |--------------------------------------------------------------------------
        */

        $getSizes = function ($type) use (
            $adultClothingSizes,
            $adultPantsSizes,
            $adultShoeSizes,
            $kidsClothingSizes,
            $kidsPantsSizes,
            $kidsShoeSizes
        ) {

            return match ($type) {

                'clothing' => collect([
                    $adultClothingSizes->get('S'),
                    $adultClothingSizes->get('M'),
                    $adultClothingSizes->get('L'),
                    $adultClothingSizes->get('XL'),
                ])->filter(),

                'pants' => collect([
                    $adultPantsSizes->get('30'),
                    $adultPantsSizes->get('32'),
                    $adultPantsSizes->get('34'),
                    $adultPantsSizes->get('36'),
                ])->filter(),

                'shoe' => collect([
                    $adultShoeSizes->get('39'),
                    $adultShoeSizes->get('41'),
                    $adultShoeSizes->get('43'),
                    $adultShoeSizes->get('45'),
                ])->filter(),

                'kids_clothing' => collect([
                    $kidsClothingSizes->get('S'),
                    $kidsClothingSizes->get('M'),
                    $kidsClothingSizes->get('L'),
                    $kidsClothingSizes->get('XL'),
                ])->filter(),

                'kids_pants' => collect([
                    $kidsPantsSizes->get('30'),
                    $kidsPantsSizes->get('32'),
                    $kidsPantsSizes->get('34'),
                    $kidsPantsSizes->get('36'),
                ])->filter(),

                'kids_shoe' => collect([
                    $kidsShoeSizes->get('39'),
                    $kidsShoeSizes->get('41'),
                    $kidsShoeSizes->get('43'),
                    $kidsShoeSizes->get('45'),
                ])->filter(),

                // Bags, caps, balls
                'accessory' => collect([null]),

                default => collect([null]),
            };
        };

        /*
        |--------------------------------------------------------------------------
        | Create Variants
        |--------------------------------------------------------------------------
        */

        $productsProcessed = 0;
        $variantsCreated = 0;

        DB::transaction(function () use (
            $products,
            $colors,
            $imageMap,
            $getProductType,
            $getSizes,
            &$productsProcessed,
            &$variantsCreated
        ) {

            foreach ($products as $product) {

                $productSlug = Str::slug($product->name);

                /*
                | No image => skip
                */
                if (!isset($imageMap[$productSlug])) {

                    $this->command?->warn(
                        "Image not found: {$product->name}"
                    );

                    continue;
                }

                $productsProcessed++;

                $type = $getProductType($product->name);

                $productSizes = $getSizes($type);

                /*
                |--------------------------------------------------------------------------
                | Each image represents a color
                |--------------------------------------------------------------------------
                */

                foreach ($imageMap[$productSlug] as $colorName => $imageId) {

                    $colorId = $colors->get(
                        strtolower($colorName)
                    )?->id;

                    if (!$colorId) {

                        $this->command?->warn(
                            "Color not found: {$colorName} - {$product->name}"
                        );

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 3-5 sizes for each color
                    |--------------------------------------------------------------------------
                    */

                    foreach ($productSizes as $size) {

                        $sizeId = $size?->id;

                        /*
                        | Check duplicate
                        */

                        $query = DB::table('product_variants')
                            ->where('product_id', $product->id)
                            ->where('color_id', $colorId);

                        if ($sizeId === null) {
                            $query->whereNull('size_id');
                        } else {
                            $query->where('size_id', $sizeId);
                        }

                        if ($query->exists()) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | SKU
                        |--------------------------------------------------------------------------
                        */

                        $sku = strtoupper(
                            Str::slug($product->name)
                            . '-'
                            . Str::slug($colorName)
                            . ($size ? '-' . Str::slug($size->name) : '')
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Insert into product_variants
                        |--------------------------------------------------------------------------
                        */

                        DB::table('product_variants')->insert([
                            'product_id'     => $product->id,
                            'size_id'        => $sizeId,
                            'color_id'       => $colorId,
                            'image_id'       => $imageId,
                            'sku'            => $sku,
                            'stock'          => rand(10, 50),
                            'price_modifier' => 0,
                            'is_active'      => true,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);

                        $variantsCreated++;
                    }
                }
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        $this->command?->info(
            "Products processed: {$productsProcessed}"
        );

        $this->command?->info(
            "Variants created: {$variantsCreated}"
        );
    }
}
