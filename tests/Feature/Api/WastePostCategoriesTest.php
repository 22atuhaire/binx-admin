<?php

namespace Tests\Feature\Api;

use App\Models\WastePost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WastePostCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_include_supported_food_waste_types(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk();

        $categories = $response->json('categories');

        $this->assertIsArray($categories);
        $this->assertContains('cooked', $categories);
        $this->assertContains('vegetables', $categories);
        $this->assertContains('bakery', $categories);
        $this->assertContains('meat', $categories);
        $this->assertContains('mixed', $categories);
    }

    public function test_categories_include_existing_database_categories(): void
    {
        WastePost::factory()->create([
            'category' => 'seafood',
            'waste_types' => ['mixed'],
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertOk();

        $categories = $response->json('categories');

        $this->assertContains('seafood', $categories);
        $this->assertContains('meat', $categories);
    }
}
