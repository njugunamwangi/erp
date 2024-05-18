<?php

namespace Database\Factories;

use App\Enums\EquipmentType;
use App\Models\Brand;
use App\Models\Vertical;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration' => '5Y-'.rand(1000, 9999),
            'vertical_id' => fake()->randomElement(Vertical::all()->pluck('id')),
            'type' => fake()->randomElement(EquipmentType::values()),
            'brand_id' => fake()->randomElement(Brand::all()->pluck('id')),
        ];
    }
}
