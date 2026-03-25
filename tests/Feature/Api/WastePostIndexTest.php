<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WastePost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WastePostIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_can_list_available_waste_posts(): void
    {
        $collector = User::factory()->collector()->create();
        $donor = User::factory()->donor()->create(['rating' => 4.5]);

        // Create available posts
        $wastePost1 = WastePost::factory()->pending()->create([
            'donor_id' => $donor->id,
            'user_id' => $donor->id,
            'waste_types' => ['plastic', 'paper'],
            'quantity' => 12.5,
            'address' => 'Plot 12, Kampala Road',
            'instructions' => 'Call on arrival',
            'photos' => ['https://example.com/photo-1.jpg'],
        ]);

        $wastePost2 = WastePost::factory()->open()->create([
            'donor_id' => $donor->id,
            'user_id' => $donor->id,
            'waste_types' => ['glass'],
            'quantity' => 5.0,
            'address' => 'Nakasero, Kampala',
            'instructions' => 'Ring doorbell twice',
            'photos' => ['https://example.com/photo-2.jpg'],
        ]);

        // Create post that should NOT appear (taken)
        WastePost::factory()->taken()->create(['donor_id' => $donor->id]);

        $response = $this->actingAs($collector)->getJson('/api/waste-posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'waste_types',
                        'quantity',
                        'address',
                        'instructions',
                        'photos',
                        'created_at',
                        'donor' => ['id', 'name', 'rating'],
                    ],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Verify first post contains all required fields
        $first = collect($data)->first();
        $this->assertIsInt($first['id']);
        $this->assertIsArray($first['waste_types']);
        $this->assertIsNumeric($first['quantity']);
        $this->assertIsString($first['address']);
        $this->assertIsString($first['instructions']);
        $this->assertIsArray($first['photos']);
        $this->assertNotNull($first['created_at']);
        $this->assertNotNull($first['donor']);
        $this->assertEquals($donor->id, $first['donor']['id']);
        $this->assertEquals($donor->name, $first['donor']['name']);
        $this->assertEquals(4.5, $first['donor']['rating']);
    }
}
