<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            [
                'stage' => 'Lead',
                'position' => 1,
                'is_default' => true,
            ],
            [
                'stage' => 'Contact Made',
                'position' => 2,
            ],
            [
                'stage' => 'Proposal Made',
                'position' => 3,
            ],
            [
                'stage' => 'Proposal Rejected',
                'position' => 4,
            ],
            [
                'stage' => 'Customer',
                'position' => 5,
            ]
        ];

        foreach ($stages as $stage) {
            Stage::create($stage);
        }
    }
}
