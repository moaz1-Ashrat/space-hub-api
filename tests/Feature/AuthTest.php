<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\SpaceOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ==================== REGISTER ====================

    public function test_customer_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '01000000001',
            'gender' => 'male',
            'role' => 'customer',
            'favorite' => 'Gaming lounges',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'email', 'role', 'phone'],
            'profile' => ['customer', 'space_owner', 'admin'],
            'token',
            'token_type',
        ]);
        $response->assertJsonPath('data.role', 'customer');

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'customer',
        ]);

        // Customer subtype row exists
        $user = User::where('email', 'john@example.com')->first();
        $this->assertDatabaseHas('customers', ['id' => $user->id]);
    }

    public function test_owner_can_register_with_tax_number()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password123',
            'phone' => '01000000002',
            'gender' => 'female',
            'role' => 'owner',
            'tax_registration_number' => '123456789',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.role', 'space_owner');

        $user = User::where('email', 'owner@example.com')->first();
        $this->assertDatabaseHas('space_owners', [
            'id' => $user->id,
            'tax_registration_number' => '123456789',
        ]);
    }

    public function test_register_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'phone' => '01000000001',
            'gender' => 'male',
            'role' => 'customer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_register_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'email', 'password']);
    }

    // ==================== LOGIN ====================

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'data', 'token', 'token_type']);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_fails_with_invalid_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_fails_with_nonexistent_user()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_suspended_user_cannot_login()
    {
        User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Your account has been suspended',
        ]);
    }

    // ==================== ME / LOGOUT ====================

    public function test_me_returns_authenticated_user()
    {
        $user = User::factory()->create(['role' => 'customer']);
        Customer::create(['id' => $user->id, 'favorite' => null]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', $user->email);
        $response->assertJsonPath('data.role', 'customer');
        $response->assertJsonStructure(['profile' => ['customer', 'space_owner', 'admin']]);
    }

    public function test_me_fails_without_token()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logged out successfully']);

        // Token should be revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
