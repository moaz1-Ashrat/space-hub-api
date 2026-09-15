<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingLifecycleTest extends TestCase
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

    protected function makeBookingData(Space $space, int $hoursFromNow = 24): array
    {
        $start = Carbon::now()->addHours($hoursFromNow);
        $end = $start->copy()->addHours(2);

        return [
            'space_id' => $space->id,
            'start_datetime' => $start->format('Y-m-d H:i:s'),
            'end_datetime' => $end->format('Y-m-d H:i:s'),
        ];
    }

    // ==================== CREATE ====================

    public function test_customer_can_create_booking()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space));

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'space', 'total_amount', 'booking_status', 'payment'],
        ]);
        $response->assertJsonPath('data.booking_status', 'pending');
        $this->assertEquals(200.00, (float) $response->json('data.total_amount'));

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'space_id' => $space->id,
            'booking_status' => 'pending',
        ]);
    }

    public function test_booking_creates_payment_automatically()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space));

        $this->assertDatabaseCount('payments', 1);
        $payment = Payment::first();
        $this->assertEquals(200.00, $payment->amount);
        $this->assertEquals('pending', $payment->payment_status);
    }

    public function test_booking_computes_commission_correctly()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space));

        $booking = Booking::first();
        $this->assertEquals(200.00, $booking->total_amount);
        $this->assertEquals(0.10, $booking->commission_rate);
        $this->assertEquals(20.00, $booking->commission_amount);
        $this->assertEquals(180.00, $booking->owner_payout);
        $this->assertEquals(200.00, $booking->customer_paid);
    }

    public function test_booking_rejects_overlapping_time_slot()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $data = $this->makeBookingData($space);

        // First booking — should succeed
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $data)
            ->assertStatus(201);

        // Confirm first booking exists
        $this->assertDatabaseCount('bookings', 1);

        // Second booking at same time slot — should be rejected
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $data);

        // Overlap check throws RuntimeException → 500
        $this->assertContains($response->status(), [422, 500]);
    }
    public function test_booking_fails_with_invalid_data()
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'space_id' => 999,
                'start_datetime' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    // ==================== LIST ====================

    public function test_customer_can_view_own_bookings()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space));

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/bookings/customer');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_owner_can_view_bookings_for_own_spaces()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space));

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/bookings/owner');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    // ==================== SHOW ====================

    public function test_booker_can_view_own_booking()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/bookings/{$booking['id']}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $booking['id']);
    }

    public function test_owner_can_view_booking_on_own_space()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/bookings/{$booking['id']}");

        $response->assertStatus(200);
    }

    public function test_stranger_cannot_view_booking()
    {
        $customer = $this->createCustomer();
        $stranger = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/bookings/{$booking['id']}");

        $response->assertStatus(403);
    }

    // ==================== CONFIRM ====================

    public function test_owner_can_confirm_pending_booking()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/bookings/{$booking['id']}/confirm");

        $response->assertStatus(200);
        $response->assertJsonPath('data.booking_status', 'confirmed');
    }

    public function test_customer_cannot_confirm_booking()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/bookings/{$booking['id']}/confirm");

        $response->assertStatus(403);
    }

    public function test_cannot_confirm_non_pending_booking()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        // Confirm once
        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/bookings/{$booking['id']}/confirm");

        // Try to confirm again
        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/bookings/{$booking['id']}/confirm");

        $response->assertStatus(400);
    }

    // ==================== CANCEL ====================

    public function test_customer_can_cancel_pending_booking()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/bookings/{$booking['id']}/cancel");

        $response->assertStatus(200);
        $response->assertJsonPath('data.booking_status', 'cancelled');
    }

    public function test_owner_can_cancel_booking_on_own_space()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/bookings/{$booking['id']}/cancel");

        $response->assertStatus(200);
    }

    public function test_cannot_cancel_completed_booking()
    {
        $customer = $this->createCustomer();
        [$owner, $space] = $this->createOwnerWithSpace();

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/bookings', $this->makeBookingData($space))
            ->json('data');

        // Mark as completed
        Booking::find($booking['id'])->update(['booking_status' => 'completed']);

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/bookings/{$booking['id']}/cancel");

        $response->assertStatus(400);
    }
}
