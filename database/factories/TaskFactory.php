<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'assigned_by' => 1,
            'assigned_to' => fake()->randomElement(Role::find(Role::STAFF)->users()->get()->pluck('id')),
            'assigned_for' => fake()->randomElement(Role::find(Role::CUSTOMER)->users()->get()->pluck('id')),
            'description' => fake()->paragraph(),
            'due_date' => fake()->dateTimeThisYear()->format('Y-m-d'),
        ];
    }
}
