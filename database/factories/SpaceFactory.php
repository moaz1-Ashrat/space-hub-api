<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Space;
use App\Models\User;

class SpaceFactory extends Factory
{
    protected $model = Space::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company,
            'location' => $this->faker->address,
            'description' => $this->faker->sentence,
            'space_type' => $this->faker->randomElement(['office', 'entertainment', 'studio']),
            'space_size' => $this->faker->numberBetween(20, 200) . ' sqm',
            'capacity_people' => $this->faker->numberBetween(1, 50),
            'price_per_hour' => $this->faker->randomFloat(2, 10, 300),
            'approval_status' => 'approved',
            // booking_date / start_time / end_time not part of factory
        ];
    }
}
