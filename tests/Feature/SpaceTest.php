<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceTest extends TestCase
{
    use RefreshDatabase;

    protected function createOwner(): User
    {
        return User::factory()->create(['role' => 'space_owner']);
    }

    protected function createCustomer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    // ==================== PUBLIC LIST ====================

    public function test_public_can_list_approved_spaces_only()
    {
        $owner = $this->createOwner();

        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'name' => 'Approved Space',
        ]);
        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'pending',
            'name' => 'Pending Space',
        ]);
        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'rejected',
            'name' => 'Rejected Space',
        ]);

        $response = $this->getJson('/api/v1/spaces');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Approved Space');
    }

    public function test_public_list_supports_type_filter()
    {
        $owner = $this->createOwner();

        Space::factory()->count(2)->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'space_type' => 'office',
        ]);
        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'space_type' => 'studio',
        ]);

        $response = $this->getJson('/api/v1/spaces?type=office');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_public_list_supports_price_filters()
    {
        $owner = $this->createOwner();

        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'price_per_hour' => 50,
        ]);
        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'price_per_hour' => 500,
        ]);

        $response = $this->getJson('/api/v1/spaces?min_price=100&max_price=600');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_public_list_supports_capacity_filter()
    {
        $owner = $this->createOwner();

        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'capacity_people' => 5,
        ]);
        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
            'capacity_people' => 50,
        ]);

        $response = $this->getJson('/api/v1/spaces?capacity=20');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    // ==================== SHOW ====================

    public function test_public_can_view_approved_space_detail()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $space->id);
        $response->assertJsonStructure(['data' => ['id', 'name', 'features']]);
    }

    public function test_public_cannot_view_unapproved_space()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(404);
    }

    public function test_owner_can_view_own_unapproved_space()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(200);
    }

    // ==================== CREATE ====================

    public function test_owner_can_create_space()
    {
        $owner = $this->createOwner();
        $feature = Feature::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'name' => 'New Studio',
                'location' => 'Cairo',
                'description' => 'Great studio',
                'space_size' => '80 sqm',
                'capacity_people' => 20,
                'price_per_hour' => 150,
                'space_type' => 'studio',
                'device_type' => 'Projector',
                'feature_ids' => [$feature->id],
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'New Studio');
        $response->assertJsonPath('data.approval_status', 'pending');

        $this->assertDatabaseHas('spaces', [
            'name' => 'New Studio',
            'approval_status' => 'pending',
            'user_id' => $owner->id,
        ]);
    }

    public function test_customer_cannot_create_space()
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'name' => 'Bad Space',
                'location' => 'Cairo',
                'space_size' => '80 sqm',
                'capacity_people' => 20,
                'price_per_hour' => 150,
                'space_type' => 'studio',
            ]);

        $response->assertStatus(403);
    }

    public function test_approval_status_is_forced_to_pending()
    {
        $owner = $this->createOwner();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'name' => 'Sneaky Space',
                'location' => 'Cairo',
                'space_size' => '80 sqm',
                'capacity_people' => 20,
                'price_per_hour' => 150,
                'space_type' => 'studio',
                'approval_status' => 'approved', // محاولة غش
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.approval_status', 'pending');
    }

    public function test_create_space_fails_with_invalid_data()
    {
        $owner = $this->createOwner();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/spaces', [
                'name' => '',
                'capacity_people' => -5,
            ]);

        $response->assertStatus(422);
    }

    // ==================== UPDATE ====================

    public function test_owner_can_update_own_space()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/spaces/{$space->id}", [
                'name' => 'Updated Name',
                'price_per_hour' => 200,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('spaces', [
            'id' => $space->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_owner_cannot_update_others_space()
    {
        $owner1 = $this->createOwner();
        $owner2 = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner1->id]);

        $response = $this->actingAs($owner2, 'sanctum')
            ->putJson("/api/v1/spaces/{$space->id}", [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    // ==================== DELETE ====================

    public function test_owner_can_delete_own_space()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(200);

        // Soft deleted
        $this->assertSoftDeleted('spaces', ['id' => $space->id]);
    }

    public function test_owner_cannot_delete_others_space()
    {
        $owner1 = $this->createOwner();
        $owner2 = $this->createOwner();
        $space = Space::factory()->create(['user_id' => $owner1->id]);

        $response = $this->actingAs($owner2, 'sanctum')
            ->deleteJson("/api/v1/spaces/{$space->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('spaces', ['id' => $space->id, 'deleted_at' => null]);
    }

    // ==================== MY SPACES ====================

    public function test_owner_can_list_own_spaces_including_unapproved()
    {
        $owner = $this->createOwner();

        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'approved',
        ]);
        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'pending',
        ]);
        Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'rejected',
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/spaces/owner/me');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_customer_cannot_access_my_spaces()
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/spaces/owner/me');

        $response->assertStatus(403);
    }
}
