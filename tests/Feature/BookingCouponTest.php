<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCustomer(): User
    {
        $user = User::factory()->create(['role' => 'customer']);
        Customer::create(['id' => $user->id, 'favorite' => null]);
        return $user;
    }

    protected function makeSpace(): Space
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        return Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'price_per_hour' => 100.00,
        ]);
    }

    public function test_booking_with_valid_coupon_applies_discount()
    {
        $customer = $this->makeCustomer();
        $space = $this->makeSpace();

        $coupon = Coupon::create([
            'user_id' => $customer->id,
            'code' => 'SAVE20',
            'discount_value' => 20,
            'commission_rate' => 0.05,
            'is_used' => false,
            'expiry_date' => now()->addDays(10),
        ]);

        $start = now()->addDay()->format('Y-m-d H:i:s');
        $end = now()->addDay()->addHours(2)->format('Y-m-d H:i:s');

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'space_id' => $space->id,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'coupon_code' => 'SAVE20',
            ]);

        $response->assertStatus(201);

        $booking = Booking::first();
        $this->assertEquals(200.00, $booking->total_amount);
        $this->assertEquals(180.00, $booking->customer_paid);
        $this->assertEquals(0.05, $booking->commission_rate);
        $this->assertEquals(10.00, $booking->commission_amount);
        $this->assertEquals(190.00, $booking->owner_payout);

        $this->assertTrue($coupon->fresh()->is_used);
    }

    public function test_booking_with_invalid_coupon_fails()
    {
        $customer = $this->makeCustomer();
        $space = $this->makeSpace();

        $start = now()->addDay()->format('Y-m-d H:i:s');
        $end = now()->addDay()->addHours(2)->format('Y-m-d H:i:s');

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'space_id' => $space->id,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'coupon_code' => 'INVALID',
            ]);

        $response->assertStatus(500); // RuntimeException
    }
}