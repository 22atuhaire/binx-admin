<?php

namespace Tests\Feature\Admin;

use App\Models\CollectionJob;
use App\Models\User;
use App\Models\WastePost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WastePostsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $collector;

    protected User $donor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->collector = User::factory()->collector()->create([
            'name' => 'Active Collector',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->donor = User::factory()->create([
            'name' => 'Test Donor',
            'role' => User::ROLE_DONOR,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_view_all_waste_posts(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Recyclable Plastics',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.waste-posts.index'));

        $response->assertOk();
        $response->assertSee('All Waste Posts');
        $response->assertSee('Recyclable Plastics');
    }

    public function test_non_admin_cannot_view_waste_posts(): void
    {
        $response = $this->actingAs($this->donor)->get(route('admin.waste-posts.index'));

        $response->assertForbidden();
    }

    public function test_can_filter_waste_posts_by_status(): void
    {
        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Available Post',
            'status' => WastePost::STATUS_OPEN,
        ]);

        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Completed Post',
            'status' => WastePost::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.waste-posts.index', ['status' => 'open']));

        $response->assertOk();
        $response->assertSee('Available Post');
        $response->assertDontSee('Completed Post');
    }

    public function test_can_filter_waste_posts_by_category(): void
    {
        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Plastic Bottles',
            'category' => 'plastic',
        ]);

        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Paper Waste',
            'category' => 'paper',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.waste-posts.index', ['category' => 'plastic']));

        $response->assertOk();
        $response->assertSee('Plastic Bottles');
        $response->assertDontSee('Paper Waste');
    }

    public function test_can_search_waste_posts_by_title(): void
    {
        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Recyclable Plastics',
        ]);

        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Old Newspapers',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.waste-posts.index', ['search' => 'Recyclable']));

        $response->assertOk();
        $response->assertSee('Recyclable Plastics');
        $response->assertDontSee('Old Newspapers');
    }

    public function test_can_search_waste_posts_by_donor_name(): void
    {
        $donor1 = User::factory()->create([
            'name' => 'John Donor',
            'role' => User::ROLE_DONOR,
        ]);

        $donor2 = User::factory()->create([
            'name' => 'Jane Smith',
            'role' => User::ROLE_DONOR,
        ]);

        WastePost::factory()->create([
            'user_id' => $donor1->id,
            'title' => 'Post by John',
        ]);

        WastePost::factory()->create([
            'user_id' => $donor2->id,
            'title' => 'Post by Jane',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.waste-posts.index', ['search' => 'John']));

        $response->assertOk();
        $response->assertSee('Post by John');
        $response->assertDontSee('Post by Jane');
    }

    public function test_can_filter_by_location(): void
    {
        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Kampala Post',
            'location' => 'Kampala, Uganda',
        ]);

        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Entebbe Post',
            'location' => 'Entebbe, Uganda',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.waste-posts.index', ['location' => 'Kampala']));

        $response->assertOk();
        $response->assertSee('Kampala Post');
        $response->assertDontSee('Entebbe Post');
    }

    public function test_can_filter_by_date_range(): void
    {
        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Old Post',
            'created_at' => now()->subDays(10),
        ]);

        WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Recent Post',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.waste-posts.index', [
            'from_date' => now()->subDays(2)->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Recent Post');
        $response->assertDontSee('Old Post');
    }

    public function test_admin_can_view_waste_post_details(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'Recyclable Plastics',
            'description' => 'A batch of recyclable plastics',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.waste-posts.show', $wastePost));

        $response->assertOk();
        $response->assertSee('Recyclable Plastics');
        $response->assertSee('A batch of recyclable plastics');
        $response->assertSee($this->donor->name);
    }

    public function test_admin_can_delete_waste_post(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'title' => 'To Be Deleted',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.waste-posts.destroy', $wastePost));

        $response->assertRedirect(route('admin.waste-posts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('waste_posts', [
            'id' => $wastePost->id,
        ]);
    }

    public function test_admin_can_assign_collector_to_waste_post(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.waste-posts.assign', $wastePost),
            ['collector_id' => $this->collector->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('collection_jobs', [
            'waste_post_id' => $wastePost->id,
            'collector_id' => $this->collector->id,
            'status' => 'pending',
        ]);

        $this->assertEquals(WastePost::STATUS_TAKEN, $wastePost->fresh()->status);
    }

    public function test_cannot_assign_non_collector_to_waste_post(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.waste-posts.assign', $wastePost),
            ['collector_id' => $this->donor->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('collection_jobs', [
            'waste_post_id' => $wastePost->id,
            'collector_id' => $this->donor->id,
        ]);
    }

    public function test_cannot_assign_inactive_collector_to_waste_post(): void
    {
        $inactiveCollector = User::factory()->collector()->create([
            'status' => User::STATUS_PENDING,
        ]);

        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.waste-posts.assign', $wastePost),
            ['collector_id' => $inactiveCollector->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_assign_collector_to_already_assigned_waste_post(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_TAKEN,
        ]);

        CollectionJob::factory()->create([
            'waste_post_id' => $wastePost->id,
            'collector_id' => $this->collector->id,
        ]);

        $otherCollector = User::factory()->collector()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.waste-posts.assign', $wastePost),
            ['collector_id' => $otherCollector->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_can_cancel_waste_post(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.waste-posts.cancel', $wastePost));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(WastePost::STATUS_CANCELLED, $wastePost->fresh()->status);
    }

    public function test_cannot_cancel_completed_waste_post(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.waste-posts.cancel', $wastePost));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(WastePost::STATUS_COMPLETED, $wastePost->fresh()->status);
    }

    public function test_waste_post_shows_assigned_collector_information(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_TAKEN,
        ]);

        CollectionJob::factory()->create([
            'waste_post_id' => $wastePost->id,
            'collector_id' => $this->collector->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.waste-posts.show', $wastePost));

        $response->assertOk();
        $response->assertSee($this->collector->name);
        $response->assertSee('Assigned Collector');
    }

    public function test_waste_post_shows_collection_history(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_COMPLETED,
        ]);

        CollectionJob::factory()->create([
            'waste_post_id' => $wastePost->id,
            'collector_id' => $this->collector->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.waste-posts.show', $wastePost));

        $response->assertOk();
        $response->assertSee('Collection History');
        $response->assertSee($this->collector->name);
    }

    public function test_requires_collector_id_to_assign(): void
    {
        $wastePost = WastePost::factory()->create([
            'user_id' => $this->donor->id,
            'status' => WastePost::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.waste-posts.assign', $wastePost),
            ['collector_id' => '']
        );

        $response->assertSessionHasErrors(['collector_id']);
    }
}
