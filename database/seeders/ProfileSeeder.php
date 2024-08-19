<?php

namespace Database\Seeders;

use App\Enums\EntityType;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Profile::create([
            'currency_id' => 80,
            'entity' => EntityType::DEFAULT,
        ]);
    }
}
