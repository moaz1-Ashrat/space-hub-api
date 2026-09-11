<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Space;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+30 days');
        $end = (clone $start)->modify('+' . fake()->numberBetween(1, 8) . ' hours');

        return [
            'user_id' => User::factory()->state(['role' => 'customer']),
            'space_id' => Space::factory(),
            // payment must exist first (non-null FK)
            'payment_id' => Payment::factory(),
            'booking_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'total_amount' => fake()->randomFloat(2, 100, 8000),
            'booking_status' => fake()->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'attendance_status' => fake()->randomElement(['unknown', 'attended', 'no_show']),
            'historical_booking' => false,
        ];
    }
}
