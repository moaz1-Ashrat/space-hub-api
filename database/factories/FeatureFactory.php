<?php

namespace Database\Factories;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feature>
 */
class FeatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'WiFi',
                'Projector',
                'Air Conditioning',
                'Parking',
                'Coffee Machine',
                'Gaming Consoles',
                'Sound System',
                'Whiteboard',
                'LED Screen',
                'VR Set',
            ]) . ' ' . fake()->unique()->numberBetween(1, 9999),
            'category' => fake()->randomElement([
                'connectivity',
                'equipment',
                'comfort',
                'facility',
                'hospitality',
                'entertainment',
                'office',
            ]),
            'description' => fake()->sentence(),
        ];
    }
}
