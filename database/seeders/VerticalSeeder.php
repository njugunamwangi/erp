<?php

namespace Database\Seeders;

use App\Models\Vertical;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VerticalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $verticals = ['Precision Agriculture', 'Mapping', 'Surveying', 'Warehouse', 'Training', 'Utility Inspection'];

        foreach ($verticals as $vertical) {
            Vertical::create([
                'vertical' => $vertical,
                'slug' => Str::slug($vertical),
            ]);
        }
    }
}
