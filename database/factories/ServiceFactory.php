<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_id' => fake()->randomElement(Equipment::all()->pluck('id')),
            'user_id' => fake()->randomElement(Role::find(Role::TECHNICIAN)->users()->get()->pluck('id')),
            'description' => fake()->paragraph()
        ];
    }
}
