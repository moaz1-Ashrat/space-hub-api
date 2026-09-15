<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function createOwner(): User
    {
        return User::factory()->create(['role' => 'space_owner']);
    }

    // ==================== PUBLIC ====================

    public function test_public_can_view_space_availability()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner->id]);

        Availability::create([
            'space_id' => $space->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        $response = $this->getJson("/api/v1/spaces/{$space->id}/availability");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_availability_list_empty_when_none_exists()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $response = $this->getJson("/api/v1/spaces/{$space->id}/availability");

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    // ==================== CREATE ====================

    public function test_owner_can_create_availability_for_own_space()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/availability", [
                'day_of_week' => 1,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'is_available' => true,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('availabilities', [
            'space_id' => $space->id,
            'day_of_week' => 1,
        ]);
    }

    public function test_owner_cannot_create_availability_for_others_space()
    {
        $owner1 = $this->createOwner();
        $owner2 = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner1->id]);

        $response = $this->actingAs($owner2, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/availability", [
                'day_of_week' => 1,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_availability_fails_with_invalid_day()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/availability", [
                'day_of_week' => 9, // يجب أن يكون 0-6
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('day_of_week');
    }

    public function test_create_availability_fails_with_end_before_start()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/spaces/{$space->id}/availability", [
                'day_of_week' => 1,
                'start_time' => '17:00:00',
                'end_time' => '09:00:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('end_time');
    }

    // ==================== UPDATE ====================

    public function test_owner_can_update_own_availability()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner->id]);
        $availability = Availability::create([
            'space_id' => $space->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/availability/{$availability->id}", [
                'is_available' => false,
                'start_time' => '10:00:00',
                'end_time' => '18:00:00',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('availabilities', [
            'id' => $availability->id,
            'is_available' => false,
        ]);
    }

    public function test_owner_cannot_update_others_availability()
    {
        $owner1 = $this->createOwner();
        $owner2 = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner1->id]);
        $availability = Availability::create([
            'space_id' => $space->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        $response = $this->actingAs($owner2, 'sanctum')
            ->putJson("/api/v1/availability/{$availability->id}", [
                'is_available' => false,
            ]);

        $response->assertStatus(403);
    }
}
