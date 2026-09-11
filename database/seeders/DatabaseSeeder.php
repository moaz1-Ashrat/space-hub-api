<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Space;
use App\Models\Feature;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\SpaceOwner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Features pool
        $features = collect([
            ['name' => 'WiFi', 'category' => 'connectivity', 'description' => 'High speed internet'],
            ['name' => 'Projector', 'category' => 'equipment', 'description' => '4K projector'],
            ['name' => 'Air Conditioning', 'category' => 'comfort', 'description' => 'Central AC'],
            ['name' => 'Parking', 'category' => 'facility', 'description' => 'Private parking'],
            ['name' => 'Coffee Machine', 'category' => 'hospitality', 'description' => 'Self-service coffee'],
            ['name' => 'Gaming Consoles', 'category' => 'entertainment', 'description' => 'PS5 / Xbox'],
            ['name' => 'Sound System', 'category' => 'equipment', 'description' => 'Professional speakers'],
            ['name' => 'Whiteboard', 'category' => 'office', 'description' => 'Large whiteboard'],
        ])->map(fn ($feature) => Feature::create($feature));

        // 2) 5 space owners + subtype rows
        $ownerUsers = User::factory()->count(5)->create([
            'role' => 'space_owner',
        ]);

        foreach ($ownerUsers as $ownerUser) {
            SpaceOwner::create([
                'id' => $ownerUser->id,
                'tax_registration_number' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            ]);
        }

        // 3) 15 spaces across owners, each with 2-4 features
        $spaces = collect();

        foreach (range(1, 15) as $i) {
            $space = Space::factory()->create([
                'user_id' => $ownerUsers->random()->id,
                'approval_status' => fake()->randomElement(['approved', 'approved', 'pending', 'rejected']),
            ]);

            $featureIds = $features
                ->random(fake()->numberBetween(2, 4))
                ->unique('id')
                ->pluck('id')
                ->all();

            $space->features()->attach($featureIds);

            $spaces->push($space);
        }

        // 4) 10 customers + subtype rows
        $customerUsers = User::factory()->count(10)->create([
            'role' => 'customer',
        ]);

        foreach ($customerUsers as $customerUser) {
            Customer::create([
                'id' => $customerUser->id,
                'favorite' => fake()->optional()->sentence(),
            ]);
        }

        // 5) 30 bookings, each with payment created first
        $statuses = array_merge(
            array_fill(0, 8, 'pending'),
            array_fill(0, 10, 'confirmed'),
            array_fill(0, 8, 'completed'),
            array_fill(0, 4, 'cancelled')
        );
        shuffle($statuses);

        foreach (range(0, 29) as $i) {
            $space = $spaces->random();
            $status = $statuses[$i];

            $amount = fake()->randomFloat(2, 100, 8000);

            $paymentStatus = match ($status) {
                'pending' => 'pending',
                'cancelled' => fake()->randomElement(['pending', 'failed']),
                default => 'paid',
            };

            $payment = Payment::create([
                'amount' => $amount,
                'payment_methode' => fake()->randomElement(['card', 'wallet', 'bank']),
                'payment_date_time' => $paymentStatus === 'pending'
                    ? null
                    : now()->subDays(fake()->numberBetween(0, 30)),
                'payment_status' => $paymentStatus,
            ]);

            $startHour = fake()->numberBetween(8, 20);
            $durationHours = fake()->numberBetween(1, 3);
            $endHour = min($startHour + $durationHours, 23);

            Booking::create([
                'user_id' => $customerUsers->random()->id,
                'space_id' => $space->id,
                'payment_id' => $payment->id,
                'booking_date' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
                'start_time' => sprintf('%02d:00:00', $startHour),
                'end_time' => sprintf('%02d:00:00', $endHour),
                'total_amount' => $amount,
                'booking_status' => $status,
                'attendance_status' => fake()->randomElement(['unknown', 'attended', 'no_show']),
                'historical_booking' => in_array($status, ['completed', 'cancelled'], true),
            ]);
        }
    }
}
