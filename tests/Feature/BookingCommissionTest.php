<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Space;
use App\Models\Coupon;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Carbon\Carbon;
use Mockery;

class BookingCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_without_coupon()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'price_per_hour' => 100.00,
        ]);

        /** @var \App\Models\User $customer */
        $customer = User::factory()->create(['role' => 'customer']);

        $start = Carbon::now()->addDay()->format('Y-m-d H:i:s');
        $end = Carbon::parse($start)->addHours(2)->format('Y-m-d H:i:s');

        $this->actingAs($customer, 'sanctum');

        $response = $this->postJson('/api/v1/bookings', [
            'space_id' => $space->id,
            'start_datetime' => $start,
            'end_datetime' => $end,
        ]);

        $response->assertStatus(201);

        $respData = $response->json('data');

        $this->assertEquals(200.00, $respData['total_amount']);
        $this->assertEquals(200.00, $respData['customer_paid']);
        $this->assertArrayNotHasKey('commission_rate', $respData);

        $this->assertDatabaseHas('payments', [
            'amount' => 200.00,
            'payment_status' => 'pending',
        ]);

        $booking = Booking::first();
        $this->assertEquals(0.10, $booking->commission_rate);
        $this->assertEquals(20.00, $booking->commission_amount);
        $this->assertEquals(180.00, $booking->owner_payout);
    }

    public function test_commission_with_coupon_reduces_commission()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'price_per_hour' => 50.00,
        ]);

        /** @var \App\Models\User $customer */
        $customer = User::factory()->create(['role' => 'customer']);

        if (! class_exists(\App\Models\Coupon::class)) {
            $this->markTestSkipped('Coupon model not present');
            return;
        }

        $coupon = Coupon::create([
            'user_id' => $customer->id,
            'discount_value' => 20.00,
            'commission_rate' => 0.05,
            'is_used' => false,
        ]);

        $start = Carbon::now()->addDay()->format('Y-m-d H:i:s');
        $end = Carbon::parse($start)->addHours(3)->format('Y-m-d H:i:s');

        $this->actingAs($customer, 'sanctum');

        $response = $this->postJson('/api/v1/bookings', [
            'space_id' => $space->id,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'coupon_id' => $coupon->id,
        ]);

        $response->assertStatus(201);

        $booking = Booking::first();

        $this->assertEquals(150.00, $booking->total_amount);
        $this->assertEquals(130.00, $booking->customer_paid);
        $this->assertDatabaseHas('payments', ['amount' => 130.00]);
        $this->assertEquals(0.05, $booking->commission_rate);
        $this->assertEquals(7.50, $booking->commission_amount);
        $this->assertEquals(142.50, $booking->owner_payout);
    }

    public function test_transaction_rolls_back_if_booking_create_fails()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'price_per_hour' => 100.00,
        ]);

        /** @var \App\Models\User $customer */
        $customer = User::factory()->create(['role' => 'customer']);

        $start = Carbon::now()->addDay()->format('Y-m-d H:i:s');
        $end = Carbon::parse($start)->addHour()->format('Y-m-d H:i:s');

        /** @var \App\Models\Booking|\Mockery\MockInterface $mockBooking */
        $mockBooking = Mockery::mock(\App\Models\Booking::class)->makePartial();
        $mockBooking->shouldReceive('where')->andReturnSelf();
        $mockBooking->shouldReceive('create')->andThrow(new \Exception('Simulated create failure'));

        $realPayment = new \App\Models\Payment();
        $realSpace = new \App\Models\Space();

        $service = new BookingService($mockBooking, $realPayment, $realSpace, null);

        $this->expectException(\Exception::class);

        try {
            $service->createBooking([
                'user_id' => $customer->id,
                'space_id' => $space->id,
                'start_datetime' => $start,
                'end_datetime' => $end,
            ]);
        } finally {
            $this->assertDatabaseCount('payments', 0);
            $this->assertDatabaseCount('bookings', 0);
        }
    }
}
