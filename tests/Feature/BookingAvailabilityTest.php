<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Customer;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function createCustomer(): User
    {
        $user = User::factory()->create(['role' => 'customer']);
        Customer::create(['id' => $user->id, 'favorite' => null]);
        return $user;
    }

    protected function createOwnerWithSpace(): array
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'price_per_hour' => 100.00,
        ]);
        return [$owner, $space];
    }

    public function test_booking_rejected_when_outside_availability_hours()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        // Monday (1) 09:00 - 17:00
        Availability::create([
            'space_id' => $space->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        // Try to book Monday 20:00 - 22:00 (outside)
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'space_id' => $space->id,
                'start_datetime' => "{$nextMonday} 20:00:00",
                'end_datetime' => "{$nextMonday} 22:00:00",
            ]);

        $response->assertStatus(500);
        $this->assertStringContainsString(
            'outside the space availability',
            $response->json('message') ?? ''
        );
    }

    public function test_booking_rejected_on_unavailable_day()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        // Only Monday available
        Availability::create([
            'space_id' => $space->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        // Try to book Tuesday
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'space_id' => $space->id,
                'start_datetime' => "{$nextTuesday} 10:00:00",
                'end_datetime' => "{$nextTuesday} 12:00:00",
            ]);

        $response->assertStatus(500);
    }

    public function test_booking_allowed_when_inside_availability()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        Availability::create([
            'space_id' => $space->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'space_id' => $space->id,
                'start_datetime' => "{$nextMonday} 10:00:00",
                'end_datetime' => "{$nextMonday} 12:00:00",
            ]);

        $response->assertStatus(201);
    }

    public function test_booking_allowed_when_no_availability_set()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        // No availability records

        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'space_id' => $space->id,
                'start_datetime' => "{$nextMonday} 03:00:00",
                'end_datetime' => "{$nextMonday} 05:00:00",
            ]);

        // Lenient mode: allow when no availability exists
        $response->assertStatus(201);
    }
}