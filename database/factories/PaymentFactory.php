<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'paid', 'failed']);

        return [
            'amount' => fake()->randomFloat(2, 100, 5000),
            'payment_methode' => fake()->randomElement(['card', 'wallet', 'bank']),
            'payment_date_time' => $status === 'pending' ? null : fake()->dateTimeBetween('-3 months', 'now'),
            'payment_status' => $status,
        ];
    }
}
