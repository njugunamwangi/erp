<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(7, [
            'lead_id' => null,
            'stage_id' => null,
        ])
            ->create()
            ->each(fn (User $user) => $user->assignRole(Role::STAFF));
    }
}
