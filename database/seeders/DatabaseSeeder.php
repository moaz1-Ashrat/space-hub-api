<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Space;
use App\Models\Feature;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\SpaceOwner;
use App\Models\Admin;
use App\Models\Review;
use App\Models\Availability;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================
        // 1) Features pool
        // ============================================
        $features = collect([
            ['name' => 'WiFi', 'category' => 'connectivity', 'description' => 'High speed internet'],
            ['name' => 'Projector', 'category' => 'equipment', 'description' => '4K projector'],
            ['name' => 'Air Conditioning', 'category' => 'comfort', 'description' => 'Central AC'],
            ['name' => 'Parking', 'category' => 'facility', 'description' => 'Private parking'],
            ['name' => 'Coffee Machine', 'category' => 'hospitality', 'description' => 'Self-service coffee'],
            ['name' => 'Gaming Consoles', 'category' => 'entertainment', 'description' => 'PS5 / Xbox'],
            ['name' => 'Sound System', 'category' => 'equipment', 'description' => 'Professional speakers'],
            ['name' => 'Whiteboard', 'category' => 'office', 'description' => 'Large whiteboard'],
            ['name' => 'LED Screen', 'category' => 'equipment', 'description' => 'LED display screen'],
            ['name' => 'VR Set', 'category' => 'entertainment', 'description' => 'VR headset'],
        ])->map(fn ($feature) => Feature::create($feature));

        // ============================================
        // 2) 10 space owners + subtype rows
        // ============================================
        $ownerUsers = User::factory()->count(10)->create([
            'role' => 'space_owner',
        ]);

        foreach ($ownerUsers as $ownerUser) {
            SpaceOwner::create([
                'id' => $ownerUser->id,
                'tax_registration_number' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            ]);
        }

        // ============================================
        // 3) 30 spaces across owners, each with 2-5 features
        // ============================================
        $spaces = collect();

        foreach (range(1, 30) as $i) {
            $space = Space::factory()->create([
                'user_id' => $ownerUsers->random()->id,
                'approval_status' => fake()->randomElement([
                    'approved', 'approved', 'approved', 'pending', 'rejected'
                ]),
            ]);

            $featureIds = $features
                ->random(fake()->numberBetween(2, 5))
                ->unique('id')
                ->pluck('id')
                ->all();

            $space->features()->attach($featureIds);

            // ============================================
            // 3.1) Create 3-6 availability slots per space
            // ============================================
            $daysCount = fake()->numberBetween(3, 6);
            $usedDays = [];

            for ($d = 0; $d < $daysCount; $d++) {
                $day = fake()->numberBetween(0, 6);

                // تجنب التكرار على نفس اليوم
                if (in_array($day, $usedDays)) {
                    continue;
                }
                $usedDays[] = $day;

                $startHour = fake()->numberBetween(8, 14);
                $endHour = fake()->numberBetween($startHour + 2, 22);

                Availability::create([
                    'space_id' => $space->id,
                    'day_of_week' => $day,
                    'start_time' => sprintf('%02d:00:00', $startHour),
                    'end_time' => sprintf('%02d:00:00', $endHour),
                    'is_available' => fake()->boolean(90),
                    'special_date' => null,
                ]);
            }

            $spaces->push($space);
        }

        // ============================================
        // 4) 30 customers + subtype rows
        // ============================================
        $customerUsers = User::factory()->count(30)->create([
            'role' => 'customer',
        ]);

        foreach ($customerUsers as $customerUser) {
            Customer::create([
                'id' => $customerUser->id,
                'favorite' => fake()->optional()->sentence(),
            ]);
        }

        // ============================================
        // 5) 15 coupons distributed to customers
        // ============================================
        $couponsPool = [];

        foreach (range(1, 15) as $i) {
            $customer = $customerUsers->random();

            $coupon = Coupon::create([
                'user_id' => $customer->id,
                'discount_value' => fake()->randomElement([10, 20, 30, 50, 100]),
                'commission_rate' => fake()->randomElement([0.05, 0.07]),
                'is_used' => false,
                'expiry_date' => now()->addDays(fake()->numberBetween(7, 90)),
                'used_at' => null,
                'booking_id' => null,
            ]);

            $couponsPool[] = $coupon;
        }

        // ============================================
        // 6) 100 bookings with payments
        // ============================================
        $statuses = array_merge(
            array_fill(0, 20, 'pending'),
            array_fill(0, 35, 'confirmed'),
            array_fill(0, 30, 'completed'),
            array_fill(0, 15, 'cancelled')
        );
        shuffle($statuses);

        $completedBookings = collect();

        foreach (range(0, 99) as $i) {
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
                'payment_methode' => fake()->randomElement(['card', 'wallet', 'bank', 'manual']),
                'payment_date_time' => $paymentStatus === 'pending'
                    ? null
                    : now()->subDays(fake()->numberBetween(0, 60)),
                'payment_status' => $paymentStatus,
            ]);

            $startHour = fake()->numberBetween(8, 20);
            $durationHours = fake()->numberBetween(1, 3);
            $endHour = min($startHour + $durationHours, 23);

            // Commission calculation (matches BookingService)
            $commissionRate = 0.10;
            $commissionAmount = round($amount * $commissionRate, 2);
            $ownerPayout = round($amount - $commissionAmount, 2);

            $booking = Booking::create([
                'user_id' => $customerUsers->random()->id,
                'space_id' => $space->id,
                'payment_id' => $payment->id,
                'booking_date' => now()->subDays(fake()->numberBetween(0, 90))->toDateString(),
                'start_time' => sprintf('%02d:00:00', $startHour),
                'end_time' => sprintf('%02d:00:00', $endHour),
                'total_amount' => $amount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'customer_paid' => $amount,
                'owner_payout' => $ownerPayout,
                'booking_status' => $status,
                'attendance_status' => fake()->randomElement(['unknown', 'attended', 'no_show']),
                'historical_booking' => in_array($status, ['completed', 'cancelled'], true),
            ]);

            if ($status === 'completed') {
                $completedBookings->push($booking);
            }
        }

        // ============================================
        // 7) Admin user + subtype row
        // ============================================
        $adminUser = User::create([
        'first_name' => 'Moaz',
         'last_name' => 'Ashraf',
         'email' => 'admin@spacehub.test',
         'password' => 'password',
         'phone' => '01090818778',
         'gender' => 'male',
         'role' => 'admin',
         'is_active' => true,
         ]);

        Admin::create([
            'id' => $adminUser->id,
            'level_of_authority' => 'super',
        ]);

        // ============================================
        // 8) 25 reviews from completed bookings
        // ============================================
        $reviewsCreated = 0;
        $usedCombinations = [];

        foreach ($completedBookings->shuffle() as $booking) {
            if ($reviewsCreated >= 25) {
                break;
            }

            $key = $booking->user_id . '-' . $booking->space_id;

            // تجنب التكرار (unique constraint)
            if (in_array($key, $usedCombinations)) {
                continue;
            }
            $usedCombinations[] = $key;

            Review::create([
                'customer_id' => $booking->user_id,
                'space_id' => $booking->space_id,
                'rating' => fake()->numberBetween(3, 5),
                'comment' => fake()->sentence(),
                'review_date' => now()->subDays(fake()->numberBetween(1, 60))->toDateString(),
            ]);

            $reviewsCreated++;
        }

        // ============================================
        // 9) Seed summary output
        // ============================================
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('  Database Seeding Complete');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('  Users:          ' . User::count());
        $this->command->info('  Customers:      ' . Customer::count());
        $this->command->info('  Space Owners:   ' . SpaceOwner::count());
        $this->command->info('  Admins:         ' . Admin::count());
        $this->command->info('  Features:       ' . Feature::count());
        $this->command->info('  Spaces:         ' . Space::count());
        $this->command->info('  Availabilities: ' . Availability::count());
        $this->command->info('  Bookings:       ' . Booking::count());
        $this->command->info('  Payments:       ' . Payment::count());
        $this->command->info('  Coupons:        ' . Coupon::count());
        $this->command->info('  Reviews:        ' . Review::count());
        $this->command->info('═══════════════════════════════════════════');
    }
}
