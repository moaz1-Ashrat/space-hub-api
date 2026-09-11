<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_checkout_marks_payment_paid_and_confirms_booking(): void
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $customer = User::factory()->create(['role' => 'customer']);
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $payment = Payment::factory()->create(['payment_status' => 'pending']);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'space_id' => $space->id,
            'payment_id' => $payment->id,
            'booking_status' => 'pending',
        ]);

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/payments/checkout', [
            'payment_id' => $payment->id,
            'payment_method' => 'manual',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.payment_status', 'paid');
        $this->assertNotNull($response->json('data.payment_date_time'));
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'booking_status' => 'confirmed']);
    }

    public function test_checkout_rejects_non_owner(): void
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $payment = Payment::factory()->create(['payment_status' => 'pending']);
        Booking::factory()->create([
            'user_id' => $customer->id,
            'space_id' => $space->id,
            'payment_id' => $payment->id,
        ]);

        $response = $this->actingAs($other, 'sanctum')->postJson('/api/v1/payments/checkout', [
            'payment_id' => $payment->id,
            'payment_method' => 'manual',
        ]);

        $response->assertStatus(403);
    }

    public function test_checkout_rejects_already_paid(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $spaceOwner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create(['user_id' => $spaceOwner->id]);
        $payment = Payment::factory()->create(['payment_status' => 'paid']);
        Booking::factory()->create([
            'user_id' => $user->id,
            'space_id' => $space->id,
            'payment_id' => $payment->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments/checkout', [
            'payment_id' => $payment->id,
            'payment_method' => 'manual',
        ]);

        $response->assertStatus(400);
    }

    public function test_checkout_rejects_invalid_method(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments/checkout', [
            'payment_id' => 999999,
            'payment_method' => 'card',
        ]);

        $response->assertStatus(422);
    }

    public function test_payment_history_returns_only_own_payments(): void
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $paymentA = Payment::factory()->create();
        $paymentB = Payment::factory()->create();

        Booking::factory()->create([
            'user_id' => $customerA->id,
            'space_id' => $space->id,
            'payment_id' => $paymentA->id,
        ]);
        Booking::factory()->create([
            'user_id' => $customerB->id,
            'space_id' => $space->id,
            'payment_id' => $paymentB->id,
        ]);

        $response = $this->actingAs($customerA, 'sanctum')->getJson('/api/v1/payments/history');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
