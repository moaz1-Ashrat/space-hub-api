<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCustomer(): User
    {
        $user = User::factory()->create(['role' => 'customer']);
        Customer::create(['id' => $user->id, 'favorite' => null]);
        return $user;
    }

    public function test_customer_can_view_own_valid_coupons()
    {
        $customer = $this->makeCustomer();

        Coupon::factory()->create([
            'user_id' => $customer->id,
            'code' => 'MY10',
            'is_used' => false,
            'expiry_date' => now()->addDays(10),
        ]);

        Coupon::factory()->create([
            'user_id' => $customer->id,
            'code' => 'USED',
            'is_used' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/coupons/my');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'MY10');
    }

    public function test_customer_sees_generic_coupons()
    {
        $customer = $this->makeCustomer();

        Coupon::factory()->create([
            'user_id' => null,
            'code' => 'GENERIC',
            'is_used' => false,
            'expiry_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/coupons/my');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'GENERIC');
    }
}