<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Image;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $images=[
            [
                'name'=>'NIKE-AIR-FORCE-1-07-black',
                'image_path'=>'https://m.media-amazon.com/images/I/71vgtffYe8L._AC_SY695_.jpg',
            ],
            [
                'name'=>'NIKE-AIR-FORCE-1-07-red',
                'image_path'=>'https://m.media-amazon.com/images/I/71LhdxbjBbL._AC_SY695_.jpg',
            ],
            [
                'name'=>'ADIDAS-SAMBA-OG-SHOES-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/61QpsQ9gWOL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-SAMBA-OG-SHOES-red',
                'image_path'=>'https://m.media-amazon.com/images/I/51p2Q4PO6DL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NEW-BALANCE-530-Beige',
                'image_path'=>'https://m.media-amazon.com/images/I/61Yak-oPLEL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NEW-BALANCE-530-red',
                'image_path'=>'https://m.media-amazon.com/images/I/61iB1Jy2oPL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ASICS-GEL-KAYANO-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/61YhxTS10+L._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NIKE-SPORTSWEAR-PHOENIX-FLEECE-HOODIE-green',
                'image_path'=>'https://m.media-amazon.com/images/I/71uztizDnfL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-Beige',
                'image_path'=>'https://m.media-amazon.com/images/I/61zkAUxn2TL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-Charcoal',
                'image_path'=>'https://m.media-amazon.com/images/I/717PLiBaBBL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'UNIQLO-AIRISM-COTTON-T-SHIRT-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/71-AL0XpvFL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'UNIQLO-AIRISM-COTTON-T-SHIRT-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/71KzAKYa8bL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ZARA-OVERSIZED-BLAZER-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/71NI6qL3h3L._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ZARA-OVERSIZED-BLAZER-grey-oliver',
                'image_path'=>'https://m.media-amazon.com/images/I/61hlVptW7ML._AC_SX466_.jpg',
            ],

            [
                'name'=>'NIKE-HERITAGE-CROSSBODY-BAG-green',
                'image_path'=>'https://m.media-amazon.com/images/I/616-wp2M9lL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-ADICOLOR-CLASSIC-BACKPACK-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/81tIBMgS9yL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-ADICOLOR-CLASSIC-BACKPACK-oliver',
                'image_path'=>'https://m.media-amazon.com/images/I/81HNPyxY-GL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NEW-BALANCE-CLASSIC-NB-CAP-white',
                'image_path'=>'https://m.media-amazon.com/images/I/61IV23sgHbL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NEW-BALANCE-CLASSIC-NB-CAP-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/71as0aizI3L._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'CALVIN-KLEIN-LOGO-CROSSBODY-BAG-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/61uTt6FzPzL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-ZENVY-GENTLE-SUPPORT-BRA-C7-S10',
                'image_path'=>'https://m.media-amazon.com/images/I/61l1CvavFYL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-OWN-THE-RUN-T-SHIRT-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/61UULCB12bL._AC_UL960_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'PUMA-TRAIN-ALL-DAY-LEGGINGS-white',
                'image_path'=>'https://m.media-amazon.com/images/I/61OtxJAiTWL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'PUMA-TRAIN-ALL-DAY-LEGGINGS-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/31NYA9w6mqL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'HOKA-MACH-6-chocolate',
                'image_path'=>'https://m.media-amazon.com/images/I/61DX9qg2bQL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'HOKA-MACH-6-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/51gucKES6aL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-AIR-FORCE-1-07-MENS-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/61A+G2zZjnL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-GAZELLE-SHOES-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/51QlxKmn-zL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-GAZELLE-SHOES-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/710+IXF81eL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NEW-BALANCE-574-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/61bgYUJXHPL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NEW-BALANCE-574-red',
                'image_path'=>'https://m.media-amazon.com/images/I/61Uv1O0+k8L._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'AIR-JORDAN-1-LOW-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/71e8wVmGfmL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-SPORTSWEAR-CLUB-FLEECE-HOODIE-white',
                'image_path'=>'https://m.media-amazon.com/images/I/71s-K8lttPL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-black',
                'image_path'=>'https://m.media-amazon.com/images/I/71H6k9SttTL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-green',
                'image_path'=>'https://m.media-amazon.com/images/I/71jifLCOk2L._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'LEVIS-511-SLIM-JEANS-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/71jszQQiNfL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'LACOSTE-CLASSIC-FIT-POLO-SHIRT-green',
                'image_path'=>'https://m.media-amazon.com/images/I/71xzVPOxHML._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-HERITAGE86-CAP-green',
                'image_path'=>'https://m.media-amazon.com/images/I/81IoliMDqpL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-TREFOIL-BASEBALL-CAP-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/71dFOEx7XkS._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-TREFOIL-BASEBALL-CAP-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/613oMJ08hlL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'VANS-OLD-SKOOL-BACKPACK-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/81PHq-xAFqL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'VANS-OLD-SKOOL-BACKPACK-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/91hTTHUsVoL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'THE-NORTH-FACE-BOREALIS-BACKPACK-navy',
                'image_path'=>'https://m.media-amazon.com/images/I/81ODkpnmN3L._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'THE-NORTH-FACE-BOREALIS-BACKPACK-green',
                'image_path'=>'https://m.media-amazon.com/images/I/7198fm5NifL._AC_UY327_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-DRI-FIT-ACADEMY-FOOTBALL-JERSEY-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/31X7ubkEY7L._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NIKE-DRI-FIT-ACADEMY-FOOTBALL-JERSEY-Charcoal',
                'image_path'=>'https://m.media-amazon.com/images/I/51sxf+1TT+L._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'PUMA-TRAINING-SHORTS-off-white',
                'https://m.media-amazon.com/images/I/519MJFTzHsL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'PUMA-TRAINING-SHORTS-Charcoal',
                'image_path'=>'https://m.media-amazon.com/images/I/61QlGrtEfTL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'JORDAN-DRI-FIT-SPORT-SHORTS-black',
                'https://m.media-amazon.com/images/I/21NWouiUcYL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'JORDAN-DRI-FIT-SPORT-SHORTS-red',
                'image_path'=>'https://m.media-amazon.com/images/I/41+KobkgpUL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-DUNK-LOW-OLDER-KIDS-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/61+KR9iVgRL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-GRAND-COURT-20-KIDS-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/71mgbji8SZL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NEW-BALANCE-574-KIDS-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/712Art7duGL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NEW-BALANCE-574-KIDS-green',
                'image_path'=>'https://m.media-amazon.com/images/I/51lCPZ3RFKL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'JORDAN-1-LOW-OLDER-KIDS-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/71STQTJwo-L._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'JORDAN-1-LOW-OLDER-KIDS-olive',
                'image_path'=>'https://m.media-amazon.com/images/I/61rR7r9B8SL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-AIR-MAX-1-OLDER-KIDS-olive',
                'image_path'=>'https://m.media-amazon.com/images/I/51rRXCgy-jL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-SUPERSTAR-SHOES-KIDS-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/51mVlh+N6hL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-SUPERSTAR-SHOES-KIDS-olive',
                'image_path'=>'https://m.media-amazon.com/images/I/61qDY52mMpL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NEW-BALANCE-574-CORE-KIDS-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/71OGjln75hL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NEW-BALANCE-574-CORE-KIDS-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/712Art7duGL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'JORDAN-1-MID-OLDER-KIDS-navy',
                'image_path'=>'https://m.media-amazon.com/images/I/71tE3MMOD9L._AC_SY575_.jpg',
            ],

            [
                'name'=>'NIKE-SPORTSWEAR-CLUB-FLEECE-HOODIE-KIDS-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/71TZODT6OwL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-KIDS-white',
                'image_path'=>'https://m.media-amazon.com/images/I/51anF3+BIEL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-T-SHIRT-KIDS-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/61MTBPLsfmL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'UNIQLO-KIDS-AIRISM-COTTON-T-SHIRT-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/21odGf606vL._AC_SR38,50_.jpg',
            ],
            [
                'name'=>'UNIQLO-KIDS-AIRISM-COTTON-T-SHIRT-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/71x+1HjMhKL._AC_SY550_.jpg',
            ],

            [
                'name'=>'ZARA-KIDS-PRINTED-DRESS-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/81NHUmbji0L._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-SPORTSWEAR-CLUB-FLEECE-CREW-KIDS-white',
                'image_path'=>'https://m.media-amazon.com/images/I/61uRgifjUIL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'NIKE-SPORTSWEAR-CLUB-FLEECE-CREW-KIDS-Charcoal',
                'image_path'=>'https://m.media-amazon.com/images/I/51yZPum79YL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-KIDS-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/61cwGpy7PHL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-ESSENTIALS-3-STRIPES-HOODIE-KIDS-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/71l4zXSXFfL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'LEVIS-KIDS-511-SLIM-JEANS-beige',
                'image_path'=>'https://m.media-amazon.com/images/I/51p2cB3rTbL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'LACOSTE-KIDS-POLO-SHIRT-white',
                'image_path'=>'https://m.media-amazon.com/images/I/61TlTmhI2sL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'LACOSTE-KIDS-POLO-SHIRT-Olive',
                'image_path'=>'https://m.media-amazon.com/images/I/612X8s9RDhL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-BRASILIA-KIDS-BACKPACK-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/71W1eiEVocL._AC_UL480_FMwebp_QL65_.jpg',
            ],

             [
                'name'=>'ADIDAS-CLASSIC-KIDS-BACKPACK-black',
                'image_path'=>'https://m.media-amazon.com/images/I/81pp86z8eRL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-CLASSIC-KIDS-BACKPACK-navy',
                'image_path'=>'https://m.media-amazon.com/images/I/810j8in+AvL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'VANS-KIDS-OLD-SKOOL-BACKPACK-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/81djolum3uL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'VANS-KIDS-OLD-SKOOL-BACKPACK-Charcoal',
                'image_path'=>'https://m.media-amazon.com/images/I/810j8in+AvL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'UNDER-ARMOUR-SCRIMMAGE-BACKPACK-black',
                'image_path'=>'https://m.media-amazon.com/images/I/51wyaE-4FmL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-PEGASUS-41-green',
                'image_path'=>'https://m.media-amazon.com/images/I/71h0XYFLwFL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-ULTRABOOST-5-black',
                'image_path'=>'https://m.media-amazon.com/images/I/510MuLSmJmL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ADIDAS-ULTRABOOST-5-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/61O8aBGFrXL._AC_UL480_FMwebp_QL65_.jpg',
            ],

             [
                'name'=>'ASICS-GEL-NIMBUS-27-black',
                'image_path'=>'https://m.media-amazon.com/images/I/51bnMiiK-jL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ASICS-GEL-NIMBUS-27-Charcoal',
                'image_path'=>'https://m.media-amazon.com/images/I/615CzU2LOyL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'HOKA-CLIFTON-10-white',
                'image_path'=>'https://m.media-amazon.com/images/I/41PsOTI53AL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'HOKA-CLIFTON-10-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/61-CWO2hwML._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'HOKA-CLIFTON-10-black',
                'image_path'=>'https://m.media-amazon.com/images/I/71g1WcoJDSL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-F50-LEAGUE-black',
                'image_path'=>'https://m.media-amazon.com/images/I/61RZMlM0NEL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'PUMA-FUTURE-8-PLAY-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/711AqGKW3ZL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-TIRO-LEAGUE-BALL-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/71bLwNWoPIL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-METCON-10-green',
                'image_path'=>'https://m.media-amazon.com/images/I/61hTe8GiX4L._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ADIDAS-DROPSET-3-TRAINER-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/61hfGmEn+ZL._AC_SY695_.jpg',
            ],
            [
                'name'=>'ADIDAS-DROPSET-3-TRAINER-red',
                'image_path'=>'https://m.media-amazon.com/images/I/61P5gLoL4rL._AC_SY695_.jpg',
            ],

            [
                'name'=>'REEBOK-NANO-X5-navy',
                'image_path'=>'https://m.media-amazon.com/images/I/81lutzuzWQL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'REEBOK-NANO-X5-blue',
                'image_path'=>'https://m.media-amazon.com/images/I/71okjXdsMUL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'UNDER-ARMOUR-TECH-20-T-SHIRT-grey',
                'image_path'=>'https://m.media-amazon.com/images/I/51m8WfLrsaL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'UNDER-ARMOUR-TECH-20-T-SHIRT-green',
                'image_path'=>'https://m.media-amazon.com/images/I/71lw1-0aDRL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'ASICS-GEL-ROCKET-11-brown',
                'image_path'=>'https://m.media-amazon.com/images/I/61fDEdBdaUL._AC_UL480_FMwebp_QL65_.jpg',
            ],
            [
                'name'=>'ASICS-GEL-ROCKET-11-green',
                'image_path'=>'https://m.media-amazon.com/images/I/61dJgYasLzL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'MIZUNO-WAVE-LIGHTNING-Z8-C13-S18',
                'image_path'=>'https://m.media-amazon.com/images/I/81TG7B0mnDL._AC_UL480_FMwebp_QL65_.jpg',
            ],

             [
                'name'=>'ADIDAS-CRAZYFLIGHT-6-off-white',
                'image_path'=>'https://m.media-amazon.com/images/I/71bzCS1QcEL._AC_UL480_FMwebp_QL65_.jpg',
            ],
             [
                'name'=>'ADIDAS-CRAZYFLIGHT-6-Charcoal',
                'image_path'=>'https://m.media-amazon.com/images/I/712l+qHdwYL._AC_UL480_FMwebp_QL65_.jpg',
            ],

            [
                'name'=>'NIKE-ZOOM-HYPERSET-2-Olive',
                'image_path'=>'https://m.media-amazon.com/images/I/81QZinpe85L._AC_UL480_FMwebp_QL65_.jpg',
            ],

        ];

        foreach ($images as $img) {

            /*
             * Prevent duplicate image records.
             */
            $existingImage = Image::where('name', $img['name'])->first();

            if ($existingImage) {
                $this->command->warn(
                    "Skipped: {$img['name']} already exists."
                );

                continue;
            }

            try {

                /*
                 * Upload remote image URL directly to Cloudinary.
                 */
                $uploaded = Cloudinary::uploadApi()->upload(
                    $img['image_path'],
                    [
                        'folder' => 'products',
                        'public_id' => $img['name'],
                        'resource_type' => 'image',
                    ]
                );

                /*
                 * Get Cloudinary HTTPS URL.
                 */
                $cloudinaryUrl = $uploaded['secure_url'];

                /*
                 * Save Cloudinary URL into database.
                 */
                Image::create([
                    'name' => $img['name'],
                    'image_path' => $cloudinaryUrl,
                ]);

                $this->command->info(
                    "Uploaded successfully: {$img['name']}"
                );

            } catch (\Throwable $e) {

                /*
                 * If one image fails,
                 * continue with the next image.
                 */
                $this->command->error(
                    "Failed: {$img['name']}"
                );

                $this->command->error(
                    $e->getMessage()
                );
            }
        }
    }
}
