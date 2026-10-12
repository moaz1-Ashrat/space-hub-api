<?php

namespace Tests\Feature;

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function createOwner(): User
    {
        return User::factory()->create(['role' => 'space_owner']);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Space',
            'location' => 'Cairo',
            'space_size' => '80 sqm',
            'capacity_people' => 20,
            'price_per_hour' => 100,
            'space_type' => 'studio',
        ], $overrides);
    }

    public function test_space_rejected_with_zero_price()
    {
        $owner = $this->createOwner();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/spaces', $this->validPayload(['price_per_hour' => 0]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('price_per_hour');
    }

    public function test_space_rejected_with_negative_price()
    {
        $owner = $this->createOwner();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/spaces', $this->validPayload(['price_per_hour' => -50]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('price_per_hour');
    }

    public function test_space_rejected_with_price_over_max()
    {
        $owner = $this->createOwner();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/spaces', $this->validPayload(['price_per_hour' => 200000]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('price_per_hour');
    }

    public function test_update_rejects_zero_price()
    {
        $owner = $this->createOwner();
        $space = Space::factory()->create([
            'user_id' => $owner->id,
            'price_per_hour' => 100,
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/spaces/{$space->id}", ['price_per_hour' => 0]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('price_per_hour');
    }
}