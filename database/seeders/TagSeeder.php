<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'Priority',
            'VIP',
        ];

        foreach ($tags as $tag) {
            Tag::create(['tag' => $tag]);
        }
    }
}
