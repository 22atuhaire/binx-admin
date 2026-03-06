<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingCollectorsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view the pending collectors page.
     */
    public function test_admin_can_view_pending_collectors_page(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingCollector = User::factory()->collector()->create();

        $response = $this->actingAs($admin)->get(route('admin.collectors.pending'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.collectors.pending');
        $response->assertViewHas('pendingCollectors');
        $response->assertSee($pendingCollector->name);
        $response->assertSee($pendingCollector->email);
    }

    /**
     * Test that non-admin users cannot access pending collectors page.
     */
    public function test_non_admin_cannot_view_pending_collectors_page(): void
    {
        $collector = User::factory()->collector()->create();

        $response = $this->actingAs($collector)->get(route('admin.collectors.pending'));

        $response->assertStatus(403);
    }

    /**
     * Test that unauthenticated users cannot access pending collectors page.
     */
    public function test_unauthenticated_cannot_view_pending_collectors_page(): void
    {
        $response = $this->get(route('admin.collectors.pending'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test that admin can display empty state when no pending collectors.
     */
    public function test_empty_state_when_no_pending_collectors(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.collectors.pending'));

        $response->assertStatus(200);
        $response->assertSee('No pending collectors');
        $response->assertSee('All collectors have been reviewed');
    }

    /**
     * Test that admin can approve a pending collector.
     */
    public function test_admin_can_approve_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingCollector = User::factory()->collector()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.approve', $pendingCollector)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue($pendingCollector->fresh()->isActive());
    }

    /**
     * Test that admin cannot approve a non-pending collector.
     */
    public function test_cannot_approve_non_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.approve', $activeCollector)
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid collector status.');
    }

    /**
     * Test that admin can reject a pending collector with reason.
     */
    public function test_admin_can_reject_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingCollector = User::factory()->collector()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.reject', $pendingCollector),
            ['reason' => 'Application does not meet required standards']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $updatedCollector = $pendingCollector->fresh();
        $this->assertTrue($updatedCollector->isBlocked());
        $this->assertNotNull($updatedCollector->rejection_reason);
        $this->assertEquals('Application does not meet required standards', $updatedCollector->rejection_reason);
    }

    /**
     * Test that rejection requires a reason.
     */
    public function test_rejection_requires_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingCollector = User::factory()->collector()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.reject', $pendingCollector),
            ['reason' => '']
        );

        $response->assertSessionHasErrors('reason');
        $this->assertTrue($pendingCollector->fresh()->isPending());
    }

    /**
     * Test that rejection reason must be at least 10 characters.
     */
    public function test_rejection_reason_minimum_length(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingCollector = User::factory()->collector()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.reject', $pendingCollector),
            ['reason' => 'Too short']
        );

        $response->assertSessionHasErrors('reason');
        $this->assertTrue($pendingCollector->fresh()->isPending());
    }

    /**
     * Test that rejection reason cannot exceed 500 characters.
     */
    public function test_rejection_reason_maximum_length(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingCollector = User::factory()->collector()->create();
        $longReason = str_repeat('a', 501);

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.reject', $pendingCollector),
            ['reason' => $longReason]
        );

        $response->assertSessionHasErrors('reason');
        $this->assertTrue($pendingCollector->fresh()->isPending());
    }

    /**
     * Test that page shows all required columns for collectors.
     */
    public function test_page_displays_all_required_columns(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->create([
            'name' => 'John Collector',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => 'Lagos, Nigeria',
            'id_document' => 'documents/collector_ids/john_id.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.collectors.pending'));

        $response->assertSee('John Collector');
        $response->assertSee('john@example.com');
        $response->assertSee('+1234567890');
        $response->assertSee('Lagos, Nigeria');
        $response->assertSee('Pending');
    }

    /**
     * Test that page displays pagination.
     */
    public function test_page_displays_pagination_with_many_collectors(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->collector()->count(25)->create();

        $response = $this->actingAs($admin)->get(route('admin.collectors.pending'));

        $response->assertSee('Showing');
        $response->assertSee('results');
    }

    /**
     * Test that non-collector users cannot be approved via collector approve route.
     */
    public function test_cannot_approve_non_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $donor = User::factory()->donor()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.approve', $donor)
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid collector status.');
    }
}
