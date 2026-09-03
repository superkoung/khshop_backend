<?php

namespace Database\Seeders;

use App\Models\Gender;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genders=[
            [
                "name"=>"Men",
                "slug"=>"men"
            ],
            [
                "name"=>"Women",
                "slug"=>"women"
            ],
            [
                "name"=>"Girl_kid",
                "slug"=>"girl-kid"
            ],
            [
                "name"=>"Boy_kid",
                "slug"=>"boy-kid"
            ]
        ];
        Gender::insert($genders);
    }
}
