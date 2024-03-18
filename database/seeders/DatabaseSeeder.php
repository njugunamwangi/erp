<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LeadSeeder::class);
        $this->call(StageSeeder::class);
        $this->call(TagSeeder::class);
        $this->call(VerticalSeeder::class);
        $this->call(AdminSeeder::class);

        User::factory(9)->create();
    }
}
