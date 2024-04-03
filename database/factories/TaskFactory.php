<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assigned_by' => fake()->randomElement(Role::find(Role::ADMIN)->users()->get()->pluck('id')),
            'assigned_to' => fake()->randomElement(Role::find(Role::STAFF)->users()->get()->pluck('id')),
            'assigned_for' => fake()->randomElement(Role::find(Role::CUSTOMER)->users()->get()->pluck('id')),
            'description' => fake()->paragraph(),
            'due_date' => Carbon::now()->addDays(fake()->numberBetween(101, 200)),
            'is_completed' => fake()->randomElement([true, false])
        ];
    }
}
