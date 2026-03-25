<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WastePost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WastePostStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_waste_post_with_mobile_payload(): void
    {
        $payload = [
            'waste_types' => ['cooked', 'vegetables'],
            'quantity' => 12.5,
            'notes' => 'Bags are already sorted.',
            'pickup_time' => 'Tomorrow 10:00 AM',
            'address' => 'Plot 12, Kampala Road',
            'instructions' => 'Call on arrival.',
            'photos' => ['https://example.com/photo-1.jpg'],
        ];

        $response = $this->postJson('/api/waste-posts', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Waste post created successfully',
                'data' => [
                    'status' => 'pending',
                    'estimated_pickup_time' => null,
                ],
            ])
            ->assertJsonPath('data.id', fn ($id) => is_int($id) && $id > 0);

        $wastePost = WastePost::query()->latest('id')->first();

        $this->assertNotNull($wastePost);
        $this->assertNotNull($wastePost->user_id);
        $this->assertNotNull($wastePost->donor_id);

        $this->assertDatabaseHas('waste_posts', [
            'address' => 'Plot 12, Kampala Road',
            'status' => 'pending',
            'collector_id' => null,
        ]);
    }

    public function test_authenticated_user_can_create_waste_post_with_mobile_payload(): void
    {
        $user = User::factory()->collector()->create();
        Sanctum::actingAs($user);

        $payload = [
            'waste_types' => ['cooked', 'vegetables'],
            'quantity' => 12.5,
            'notes' => 'Bags are already sorted.',
            'pickup_time' => 'Tomorrow 10:00 AM',
            'address' => 'Plot 12, Kampala Road',
            'instructions' => 'Call on arrival.',
            'photos' => ['https://example.com/photo-1.jpg'],
        ];

        $response = $this->postJson('/api/waste-posts', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Waste post created successfully',
                'data' => [
                    'status' => 'pending',
                    'estimated_pickup_time' => null,
                ],
            ])
            ->assertJsonPath('data.id', fn ($id) => is_int($id) && $id > 0);

        $this->assertDatabaseHas('waste_posts', [
            'user_id' => $user->id,
            'donor_id' => $user->id,
            'address' => 'Plot 12, Kampala Road',
            'status' => 'pending',
            'collector_id' => null,
        ]);
    }

    public function test_store_returns_validation_errors_for_invalid_mobile_payload(): void
    {
        $user = User::factory()->donor()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/waste-posts', [
            'quantity' => 0,
            'pickup_time' => '',
            'address' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors([
                'waste_types',
                'quantity',
                'pickup_time',
                'address',
            ]);
    }

    public function test_store_rejects_unsupported_waste_type(): void
    {
        $user = User::factory()->donor()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/waste-posts', [
            'waste_types' => ['plastic'],
            'quantity' => 3,
            'pickup_time' => 'Tomorrow 10:00 AM',
            'address' => 'Plot 12, Kampala Road',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors([
                'waste_types.0',
            ]);
    }
}
