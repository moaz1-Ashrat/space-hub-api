<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Admin::create(['id' => $admin->id, 'level_of_authority' => 'super']);
        return $admin;
    }

    public function test_admin_can_create_coupon()
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/coupons', [
                'code' => 'SAVE20',
                'discount_value' => 20,
                'commission_rate' => 0.05,
                'expiry_date' => now()->addDays(30)->toDateString(),
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('coupons', ['code' => 'SAVE20']);
    }

    public function test_admin_can_list_coupons()
    {
        $admin = $this->makeAdmin();
        Coupon::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/coupons');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [['id', 'code', 'discount_value']]]);
    }

    public function test_admin_can_update_coupon()
    {
        $admin = $this->makeAdmin();
        $coupon = Coupon::factory()->create(['code' => 'OLD10']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/coupons/{$coupon->id}", [
                'code' => 'NEW20',
                'discount_value' => 20,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'code' => 'NEW20']);
    }

    public function test_admin_can_delete_coupon()
    {
        $admin = $this->makeAdmin();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/coupons/{$coupon->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }
}