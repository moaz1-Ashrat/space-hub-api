<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCustomer(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(
            ['role' => 'customer'],
            $attributes
        ));

        Customer::create([
            'id' => $user->id,
            'favorite' => null,
        ]);

        return $user;
    }

    protected function makeCompletedBooking(User $customer, Space $space): Booking
    {
        $payment = Payment::create([
            'amount' => 100,
            'payment_methode' => 'manual',
            'payment_status' => 'paid',
            'payment_date_time' => now(),
        ]);

        return Booking::create([
            'user_id' => $customer->id,
            'space_id' => $space->id,
            'payment_id' => $payment->id,
            'booking_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_amount' => 100,
            'booking_status' => 'completed',
            'attendance_status' => 'attended',
            'historical_booking' => true,
        ]);
    }

    public function test_customer_can_review_completed_booking()
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $customer = $this->makeCustomer();

        $this->makeCompletedBooking($customer, $space);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'space_id' => $space->id,
                'rating' => 5,
                'comment' => 'Great space!',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'customer_id' => $customer->id,
            'space_id' => $space->id,
            'rating' => 5,
        ]);
    }

    public function test_customer_cannot_review_without_completed_booking()
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $customer = $this->makeCustomer();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'space_id' => $space->id,
                'rating' => 5,
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_review_same_space_twice()
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $customer = $this->makeCustomer();

        $this->makeCompletedBooking($customer, $space);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'space_id' => $space->id,
                'rating' => 5,
            ])
            ->assertStatus(201);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'space_id' => $space->id,
                'rating' => 3,
            ]);

        $response->assertStatus(422);
    }

    public function test_public_can_view_reviews()
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $customer = $this->makeCustomer();

        $this->makeCompletedBooking($customer, $space);

        Review::create([
            'customer_id' => $customer->id,
            'space_id' => $space->id,
            'rating' => 4,
            'comment' => 'Nice',
            'review_date' => now()->toDateString(),
        ]);

        $response = $this->getJson("/api/v1/spaces/{$space->id}/reviews");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_public_review_list_shows_only_first_name()
    {
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $customer = $this->makeCustomer([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789',
        ]);

        $this->makeCompletedBooking($customer, $space);

        Review::create([
            'customer_id' => $customer->id,
            'space_id' => $space->id,
            'rating' => 5,
            'review_date' => now()->toDateString(),
        ]);

        $response = $this->getJson("/api/v1/spaces/{$space->id}/reviews");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.customer_first_name', 'John');
        $response->assertJsonMissing(['email' => 'john@example.com']);
        $response->assertJsonMissing(['phone' => '0123456789']);
    }
}
