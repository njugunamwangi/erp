<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Task::factory(100)
            ->create()
            ->each(function (Task $task) {

                if ($task->requires_equipment) {
                    $equipment = Equipment::query()->where('vertical_id', $task->vertical_id)->pluck('id')->toArray();

                    $task->equipment()->attach($equipment);

                }
            });
    }
}
