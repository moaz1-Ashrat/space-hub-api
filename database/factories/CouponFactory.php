<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => strtoupper(fake()->unique()->bothify('????####')),
            'discount_value' => fake()->randomFloat(2, 5, 100),
            'commission_rate' => fake()->randomElement([null, 0.05, 0.10]),
            'is_used' => false,
            'expiry_date' => now()->addDays(30),
            'used_at' => null,
            'booking_id' => null,
        ];
    }
}
