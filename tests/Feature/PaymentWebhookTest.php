<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Exception\SignatureVerificationException;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makePayment(): array
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $owner = User::factory()->create(['role' => 'owner']);
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $payment = Payment::create([
            'amount' => 200.00,
            'payment_methode' => null,
            'payment_status' => 'pending',
            'payment_date_time' => null,
        ]);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'space_id' => $space->id,
            'payment_id' => $payment->id,
            'booking_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_amount' => 200.00,
            'booking_status' => 'pending',
            'attendance_status' => 'pending',
            'historical_booking' => false,
        ]);

        return [$payment, $booking];
    }

    public function test_webhook_with_valid_signature_marks_paid(): void
    {
        [$payment, $booking] = $this->makePayment();

        $fakeEvent = (object) [
            'type' => 'checkout.session.completed',
            'data' => (object) [
                'object' => (object) [
                    'metadata' => (object) ['payment_id' => $payment->id],
                ],
            ],
        ];

        $webhookMock = Mockery::mock('alias:Stripe\Webhook');
        $webhookMock->shouldReceive('constructEvent')
            ->once()
            ->andReturn($fakeEvent);

        $response = $this->postJson('/api/v1/payments/webhook', [], [
            'Stripe-Signature' => 'test_sig',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_status' => 'confirmed',
        ]);
    }

    public function test_webhook_with_invalid_signature_returns_400(): void
    {
        $webhookMock = Mockery::mock('alias:Stripe\Webhook');
        $webhookMock->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new SignatureVerificationException('Invalid signature'));

        $response = $this->postJson('/api/v1/payments/webhook', [], [
            'Stripe-Signature' => 'bad_sig',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['status' => 'error']);
    }
}
