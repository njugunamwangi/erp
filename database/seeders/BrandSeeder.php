<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'brand' => 'DJI',
                'website' => 'dji.com',
            ],
            [
                'brand' => 'XAG',
                'website' => 'xag.au',
            ],
            [
                'brand' => 'Kolida',
                'website' => 'kolida.com',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
