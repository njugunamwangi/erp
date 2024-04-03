<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Ndachi',
            'email' => 'info@ndachi.dev',
            'phone' => fake()->phoneNumber(),
            'password' => bcrypt('Password'),
        ]);

        $user->assignRole(Role::ADMIN);
    }
}
