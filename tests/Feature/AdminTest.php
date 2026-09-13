<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Admin::create(['id' => $admin->id, 'level_of_authority' => 'super']);
        return $admin;
    }

    public function test_non_admin_gets_403_on_all_admin_routes()
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $routes = [
            ['GET', '/api/v1/admin/users'],
            ['PUT', '/api/v1/admin/users/1/suspend'],
            ['PUT', '/api/v1/admin/users/1/activate'],
            ['GET', '/api/v1/admin/spaces/pending'],
            ['PUT', '/api/v1/admin/spaces/1/approve'],
            ['PUT', '/api/v1/admin/spaces/1/reject'],
            ['GET', '/api/v1/admin/transactions'],
            ['GET', '/api/v1/admin/dashboard'],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->actingAs($customer, 'sanctum')->json($method, $uri);
            $response->assertStatus(403);
        }
    }

    public function test_admin_can_list_users()
    {
        $admin = $this->makeAdmin();
        User::factory()->count(5)->create(['role' => 'customer']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [['id', 'name', 'email', 'role', 'is_active']],
        ]);
    }

    public function test_admin_can_suspend_user()
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $customer->createToken('auth_token');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/users/{$customer->id}/suspend");

        $response->assertStatus(200);
        $this->assertFalse($customer->fresh()->is_active);
        $this->assertEquals(0, $customer->tokens()->count());
    }

    public function test_admin_can_activate_user()
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/users/{$customer->id}/activate");

        $response->assertStatus(200);
        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_admin_can_approve_space()
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/spaces/{$space->id}/approve");

        $response->assertStatus(200);
        $this->assertEquals('approved', $space->fresh()->approval_status);
    }

    public function test_admin_can_reject_space()
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/spaces/{$space->id}/reject");

        $response->assertStatus(200);
        $this->assertEquals('rejected', $space->fresh()->approval_status);
    }

    public function test_admin_can_list_transactions_with_filters()
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create(['role' => 'customer']);
        $owner = User::factory()->create(['role' => 'space_owner']);
        $space = Space::factory()->create(['user_id' => $owner->id]);

        $payment = Payment::create([
            'amount' => 500,
            'payment_methode' => 'manual',
            'payment_status' => 'paid',
            'payment_date_time' => now(),
        ]);

        Booking::create([
            'user_id' => $customer->id,
            'space_id' => $space->id,
            'payment_id' => $payment->id,
            'booking_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_amount' => 500,
            'booking_status' => 'confirmed',
            'attendance_status' => 'pending',
            'historical_booking' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/transactions?status=paid');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_dashboard()
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_users',
                'total_customers',
                'total_owners',
                'total_spaces',
                'pending_spaces',
                'total_bookings',
                'total_revenue',
            ],
        ]);
    }

    public function test_suspended_user_cannot_login()
    {
        User::factory()->create([
            'role' => 'customer',
            'email' => 'suspended@test.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Your account has been suspended']);
    }
}
