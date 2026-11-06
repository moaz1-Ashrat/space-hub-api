<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Space;
use App\Models\SpaceOwner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FullDemoSeeder extends Seeder
{
    private array $maleNames = ['Ahmed','Mohamed','Mahmoud','Youssef','Omar','Ali','Hassan','Khaled','Tarek','Sherif','Karim','Mostafa','Amr','Ibrahim','Hany','Wael','Ashraf','Sameh','Ehab','Nader','Ramy','Hossam','Islam','Yasser','Bassem','Fady','Adel','Sami','Tamer','Ayman'];
    private array $femaleNames = ['Fatma','Aya','Nour','Sara','Mona','Hana','Rana','Yasmin','Salma','Layla','Dina','Rania','Heba','Shimaa','Reem','Dalia','Mai','Nada','Marwa','Amira','Hoda','Ghada','Nourhan','Menna','Eman','Noha','Yara','Farida','Habiba','Jana'];
    private array $lastNames = ['Hassan','Ibrahim','Mohamed','Ahmed','Ali','Mostafa','Mahmoud','Said','Fawzy','Farouk','Zaki','Nasser','Saleh','Kamel','Rashad','Tawfik','Sabry','Amin','Helmy','Shaker','Fahmy','Gabr','Soliman','Adel'];
    private array $locations = ['Maadi, Cairo','Zamalek, Cairo','New Cairo','Heliopolis, Cairo','Nasr City, Cairo','Downtown Cairo','Sheikh Zayed','6th of October','Mohandessin, Giza','Dokki, Giza','Smart Village, Giza','Ain Sokhna','Alexandria','El Rehab, Cairo','Madinaty, Cairo'];
    private array $spaceTemplates = [
        'coworking' => ['The Hive Coworking','Nexus Hub','Workspace Cairo','Innovation Station','Startup Base','Mind Space','Creative Corner','The Office Hub','Cowork Central','Desk Republic','Team Works','Collaboration Lab'],
        'event_hall' => ['Grand Celebration Hall','Royal Events Hall','Emerald Banquet','Golden Hall','Crystal Palace','Pearl Event Center','Diamond Reception','Regency Hall','Skyline Events','The Grand Venue'],
        'studio' => ['Studio 21','Pixel Studio','Sound Wave Studio','Frame Photography Studio','Creative Lens Studio','Film Factory','Audio Vault','Image Works','Studio One','Visual Arts Studio'],
        'entertainment' => ['Fun Zone','Game On Arena','Play Palace','VR Experience Hub','Entertainment X','Escape Room Cairo','Adventure Park','Neon Arcade','The Playground','Bowl Fun'],
        'office' => ['Executive Suite','Business Tower Office','Skyline Office','Corporate Hub','Elite Workspace','Prime Business Center','Prestige Office','The Executive Floor','Summit Office','Global Business Hub'],
    ];
    private array $deviceTypes = ['none','projector','tv','sound_system','whiteboard','mixed'];
    private array $comments = ['Excellent space, highly recommended!','Great experience overall. Will book again.','The location was perfect and the staff was friendly.','Very clean and well-maintained.','Good value for money.','Amazing atmosphere, loved every minute!','Decent space but could use some improvements.','Perfect for our team meeting.','Exactly as described. Very satisfied.','Beautiful venue, will definitely return.','A bit pricey but worth it.','The equipment was top-notch.','Fantastic experience from start to finish.','Really enjoyed our time here.','Not bad, but a few issues with the AC.'];

    public function run(): void
    {
        $this->command->info('Full Demo Seeder starting...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['coupons','reviews','bookings','payments','availabilities','space_images','spaces','admins','space_owners','customers','users'] as $t) {
            DB::table($t)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $admins = $this->createAdmins(5);
        $this->command->info('  Admins: ' . count($admins));

        $owners = $this->createOwners(35);
        $this->command->info('  Space Owners: ' . count($owners));

        $customers = $this->createCustomers(80);
        $this->command->info('  Customers: ' . count($customers));

        $spaces = $this->createSpaces($owners);
        $this->command->info('  Spaces: ' . count($spaces));

        $availCount = $this->createAvailability($spaces);
        $this->command->info('  Availability slots: ' . $availCount);

        $imgCount = $this->createImages($spaces);
        $this->command->info('  Images: ' . $imgCount);

        $coupCount = $this->createCoupons($customers);
        $this->command->info('  Coupons: ' . $coupCount);

        $bookings = $this->createBookings($customers, $spaces);
        $this->command->info('  Bookings: ' . count($bookings));

        $revCount = $this->createReviews($bookings);
        $this->command->info('  Reviews: ' . $revCount);

        $this->command->info('Done!');
    }

    private function createAdmins(int $count): array
    {
        $levels = ['super_admin','moderator','support'];
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $gender = $i % 2 === 0 ? 'male' : 'female';
            $user = User::create([
                'first_name' => $this->pick($gender === 'male' ? $this->maleNames : $this->femaleNames),
                'last_name' => $this->pick($this->lastNames),
                'email' => 'admin' . ($i + 1) . '@spacehub.test',
                'password' => 'password123',
                'phone' => '01' . rand(0, 2) . rand(10000000, 99999999),
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(28, 55))->subDays(rand(1, 365))->toDateString(),
                'role' => 'admin',
                'is_active' => true,
            ]);
            Admin::create(['id' => $user->id, 'level_of_authority' => $levels[$i % count($levels)]]);
            $result[] = $user;
        }
        return $result;
    }

    private function createOwners(int $count): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $gender = $i % 2 === 0 ? 'male' : 'female';
            $user = User::create([
                'first_name' => $this->pick($gender === 'male' ? $this->maleNames : $this->femaleNames),
                'last_name' => $this->pick($this->lastNames),
                'email' => 'owner' . ($i + 1) . '@spacehub.test',
                'password' => 'password123',
                'phone' => '01' . rand(0, 2) . rand(10000000, 99999999),
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(25, 60))->subDays(rand(1, 365))->toDateString(),
                'role' => 'space_owner',
                'is_active' => rand(1, 100) <= 95,
            ]);
            SpaceOwner::create(['id' => $user->id, 'tax_registration_number' => (string) rand(100000000, 999999999)]);
            $result[] = $user;
        }
        return $result;
    }

    private function createCustomers(int $count): array
    {
        $result = [];
        $favorites = ['coworking','event_hall','studio','entertainment','office'];
        for ($i = 0; $i < $count; $i++) {
            $gender = $i % 3 === 0 ? 'female' : 'male';
            $user = User::create([
                'first_name' => $this->pick($gender === 'male' ? $this->maleNames : $this->femaleNames),
                'last_name' => $this->pick($this->lastNames),
                'email' => 'customer' . ($i + 1) . '@spacehub.test',
                'password' => 'password123',
                'phone' => '01' . rand(0, 2) . rand(10000000, 99999999),
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(18, 55))->subDays(rand(1, 365))->toDateString(),
                'role' => 'customer',
                'is_active' => rand(1, 100) <= 97,
            ]);
            Customer::create(['id' => $user->id, 'favorite' => $this->pick($favorites)]);
            $result[] = $user;
        }
        return $result;
    }

    private function createSpaces(array $owners): array
    {
        $types = array_keys($this->spaceTemplates);
        $result = [];
        for ($i = 0; $i < 120; $i++) {
            $owner = $this->pick($owners);
            $type = $types[$i % count($types)];
            $name = $this->pick($this->spaceTemplates[$type]) . ' ' . rand(1, 999);
            $location = $this->pick($this->locations);

            $status = 'approved';
            $r = rand(1, 100);
            if ($r <= 12) $status = 'pending';
            elseif ($r <= 18) $status = 'rejected';

            $space = Space::create([
                'user_id' => $owner->id,
                'name' => $name,
                'location' => $location,
                'description' => 'A premium ' . str_replace('_', ' ', $type) . ' in ' . $location . '. Perfect for teams and individuals looking for a professional and well-equipped environment.',
                'space_size' => $this->pick(['small','medium','large','extra_large']),
                'capacity_people' => rand(2, 250),
                'price_per_hour' => rand(50, 2500),
                'space_type' => $type,
                'device_type' => $this->pick($this->deviceTypes),
                'approval_status' => $status,
            ]);
            $result[] = $space;
        }
        return $result;
    }

    private function createAvailability(array $spaces): int
    {
        $rows = [];
        $now = now();
        $allSlots = [['08:00:00','12:00:00'],['13:00:00','17:00:00'],['18:00:00','22:00:00']];

        foreach ($spaces as $space) {
            $activeDays = [];
            foreach ([0,1,2,3,4,5,6] as $d) {
                if (rand(1, 100) <= 75) $activeDays[] = $d;
            }
            if (empty($activeDays)) $activeDays = [1,2,3,4,5];

            foreach ($activeDays as $day) {
                $slots = $allSlots;
                shuffle($slots);
                $n = rand(1, 2);
                for ($s = 0; $s < $n; $s++) {
                    $rows[] = [
                        'space_id' => $space->id,
                        'day_of_week' => $day,
                        'start_time' => $slots[$s][0],
                        'end_time' => $slots[$s][1],
                        'is_available' => true,
                        'special_date' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) DB::table('availabilities')->insert($chunk);
        return count($rows);
    }

    private function createImages(array $spaces): int
    {
        $rows = [];
        $now = now();
        foreach ($spaces as $space) {
            $n = rand(2, 5);
            for ($i = 0; $i < $n; $i++) {
                $rows[] = [
                    'space_id' => $space->id,
                    'image_path' => 'https://picsum.photos/seed/sh' . $space->id . '_' . $i . '/800/600',
                    'order' => $i,
                    'is_primary' => $i === 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) DB::table('space_images')->insert($chunk);
        return count($rows);
    }

    private function createCoupons(array $customers): int
    {
        $rows = [];
        $now = now();
        for ($i = 0; $i < 150; $i++) {
            $isGeneric = rand(1, 100) <= 60;
            $userId = $isGeneric ? null : $this->pick($customers)->id;
            $isUsed = rand(1, 100) <= 35;

            $rows[] = [
                'user_id' => $userId,
                'code' => strtoupper(Str::random(4)) . rand(10, 99) . strtoupper(Str::random(2)),
                'discount_value' => $this->pick([10, 20, 30, 50, 75, 100, 150]),
                'commission_rate' => $this->pick([null, 0.05, 0.07, 0.10, 0.12]),
                'is_used' => $isUsed,
                'expiry_date' => rand(1, 100) <= 80 ? now()->addDays(rand(-30, 90))->toDateString() : null,
                'used_at' => $isUsed ? now()->subDays(rand(1, 120)) : null,
                'booking_id' => null,
                'created_at' => now()->subDays(rand(1, 180)),
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) DB::table('coupons')->insert($chunk);
        return count($rows);
    }

    private function createBookings(array $customers, array $spaces): array
    {
        $bookings = [];
        $approved = array_values(array_filter($spaces, fn ($s) => $s->approval_status === 'approved'));
        if (empty($approved)) $approved = $spaces;

        for ($i = 0; $i < 250; $i++) {
            $customer = $this->pick($customers);
            $space = $this->pick($approved);

            $r = rand(1, 100);
            if ($r <= 70) {
                $bookingDate = now()->subDays(rand(1, 180));
                $status = 'completed';
                $attendance = rand(1, 100) <= 90 ? 'attended' : 'no_show';
                $paymentStatus = 'paid';
            } elseif ($r <= 85) {
                $bookingDate = now()->addDays(rand(0, 3));
                $status = 'confirmed';
                $attendance = 'pending';
                $paymentStatus = 'paid';
            } else {
                $bookingDate = now()->addDays(rand(4, 90));
                $status = rand(1, 100) <= 70 ? 'confirmed' : 'pending';
                $attendance = 'pending';
                $paymentStatus = $status === 'pending' ? 'pending' : 'paid';
            }

            $startHour = rand(8, 19);
            $duration = rand(1, 4);
            $startTime = sprintf('%02d:00:00', $startHour);
            $endTime = sprintf('%02d:00:00', $startHour + $duration);

            $totalAmount = round((float) $space->price_per_hour * $duration, 2);
            $commissionRate = 0.10;
            $commissionAmount = round($totalAmount * $commissionRate, 2);
            $ownerPayout = round($totalAmount - $commissionAmount, 2);

            $payment = Payment::create([
                'amount' => $totalAmount,
                'payment_methode' => $this->pick(['manual','cash','card','online']),
                'payment_date_time' => $paymentStatus === 'paid' ? $bookingDate->copy()->addHours($startHour) : null,
                'payment_status' => $paymentStatus,
            ]);

            $booking = Booking::create([
                'user_id' => $customer->id,
                'space_id' => $space->id,
                'payment_id' => $payment->id,
                'booking_date' => $bookingDate->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_amount' => $totalAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'customer_paid' => $totalAmount,
                'owner_payout' => $ownerPayout,
                'booking_status' => $status,
                'attendance_status' => $attendance,
                'historical_booking' => false,
            ]);
            $bookings[] = $booking;
        }
        return $bookings;
    }

    private function createReviews(array $bookings): int
    {
        $count = 0;
        foreach ($bookings as $booking) {
            if ($booking->booking_status !== 'completed') continue;
            if (rand(1, 100) > 72) continue;

            Review::create([
                'customer_id' => $booking->user_id,
                'space_id' => $booking->space_id,
                'rating' => rand(3, 5),
                'comment' => $this->pick($this->comments),
                'review_date' => Carbon::parse($booking->booking_date)->addDays(rand(1, 14))->toDateString(),
            ]);
            $count++;
        }
        return $count;
    }

    private function pick(array $arr) { return $arr[array_rand($arr)]; }
}
