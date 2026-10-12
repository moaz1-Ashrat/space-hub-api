<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSpacesTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Admin::create(['id' => $admin->id, 'level_of_authority' => 'super']);
        return $admin;
    }

    public function test_admin_can_list_all_spaces()
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create(['role' => 'space_owner']);
        Space::factory()->count(5)->create(['user_id' => $owner->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/spaces');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [['id', 'name', 'location', 'approval_status', 'owner', 'primary_image']],
            'meta',
        ]);
    }

    public function test_admin_can_filter_spaces_by_status()
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create(['role' => 'space_owner']);
        Space::factory()->create(['user_id' => $owner->id, 'approval_status' => 'pending']);
        Space::factory()->create(['user_id' => $owner->id, 'approval_status' => 'approved']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/spaces?approval_status=pending');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_user_detail()
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create(['role' => 'space_owner']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/users/{$owner->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $owner->id);
        $response->assertJsonPath('data.role', 'space_owner');
    }

    public function test_non_admin_cannot_access_admin_spaces()
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/spaces');

        $response->assertStatus(403);
    }
}