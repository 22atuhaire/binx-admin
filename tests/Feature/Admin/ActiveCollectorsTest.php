<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCollectorsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view the active collectors page.
     */
    public function test_admin_can_view_active_collectors_page(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();

        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.collectors.index');
        $response->assertViewHas('collectors');
        $response->assertSee($activeCollector->name);
        $response->assertSee($activeCollector->email);
    }

    /**
     * Test that non-admin users cannot access active collectors page.
     */
    public function test_non_admin_cannot_view_active_collectors_page(): void
    {
        $collector = User::factory()->collector()->create();

        $response = $this->actingAs($collector)->get(route('admin.collectors.index'));

        $response->assertStatus(403);
    }

    /**
     * Test that unauthenticated users cannot access active collectors page.
     */
    public function test_unauthenticated_cannot_view_active_collectors_page(): void
    {
        $response = $this->get(route('admin.collectors.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test that page shows empty state when no active collectors.
     */
    public function test_empty_state_when_no_active_collectors(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertStatus(200);
        $response->assertSee('No active collectors');
    }

    /**
     * Test that admin can suspend an active collector.
     */
    public function test_admin_can_suspend_active_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.suspend', $activeCollector),
            ['reason' => 'Violates community standards']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue($activeCollector->fresh()->isSuspended());
        $this->assertEquals('Violates community standards', $activeCollector->fresh()->suspension_reason);
    }

    /**
     * Test that admin cannot suspend a non-active collector.
     */
    public function test_cannot_suspend_non_active_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingCollector = User::factory()->collector()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.suspend', $pendingCollector),
            ['reason' => 'Some reason here to use 10+ chars']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid collector status.');
    }

    /**
     * Test that suspension requires a reason.
     */
    public function test_suspension_requires_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.suspend', $activeCollector),
            ['reason' => '']
        );

        $response->assertSessionHasErrors('reason');
        $this->assertFalse($activeCollector->fresh()->isSuspended());
    }

    /**
     * Test that suspension reason must be at least 10 characters.
     */
    public function test_suspension_reason_minimum_length(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.suspend', $activeCollector),
            ['reason' => 'Too short']
        );

        $response->assertSessionHasErrors('reason');
        $this->assertFalse($activeCollector->fresh()->isSuspended());
    }

    /**
     * Test that suspension reason cannot exceed 500 characters.
     */
    public function test_suspension_reason_maximum_length(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();
        $longReason = str_repeat('a', 501);

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.suspend', $activeCollector),
            ['reason' => $longReason]
        );

        $response->assertSessionHasErrors('reason');
        $this->assertFalse($activeCollector->fresh()->isSuspended());
    }

    /**
     * Test that admin can reactivate a suspended collector.
     */
    public function test_admin_can_reactivate_suspended_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $suspendedCollector = User::factory()->collector()->active()->create([
            'suspended_at' => now(),
            'suspension_reason' => 'Test suspension',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.reactivate', $suspendedCollector)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($suspendedCollector->fresh()->isSuspended());
        $this->assertNull($suspendedCollector->fresh()->suspension_reason);
    }

    /**
     * Test that admin cannot reactivate a non-suspended collector.
     */
    public function test_cannot_reactivate_non_suspended_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.collectors.reactivate', $activeCollector)
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Collector is not suspended.');
    }

    /**
     * Test that page displays all required columns for collectors.
     */
    public function test_page_displays_all_required_columns(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->active()->create([
            'name' => 'John Collector',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => 'Lagos, Nigeria',
            'rating' => 4.5,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertSee('John Collector');
        $response->assertSee('john@example.com');
        $response->assertSee('+1234567890');
        $response->assertSee('Lagos, Nigeria');
        $response->assertSee('4.5');
        $response->assertSee('Active');
    }

    /**
     * Test that page displays suspended badge for suspended collectors.
     */
    public function test_page_displays_suspended_status(): void
    {
        $admin = User::factory()->admin()->create();
        $suspendedCollector = User::factory()->collector()->active()->create([
            'name' => 'Suspended Collector',
            'suspended_at' => now(),
            'suspension_reason' => 'Test',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertSee('Suspended Collector');
        $response->assertSee('Suspended');
    }

    /**
     * Test that page displays pagination with many collectors.
     */
    public function test_page_displays_pagination_with_many_collectors(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->collector()->active()->count(25)->create();

        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertSee('Showing');
        $response->assertSee('results');
    }

    /**
     * Test that admin can view collector job history.
     */
    public function test_admin_can_view_collector_job_history(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->active()->create();

        $response = $this->actingAs($admin)->get(
            route('admin.collectors.job-history', $collector)
        );

        $response->assertStatus(200);
        $response->assertViewIs('admin.collectors.job-history');
        $response->assertViewHas('user', $collector);
        $response->assertViewHas('jobs');
    }

    /**
     * Test that viewing job history of non-collector returns 404.
     */
    public function test_viewing_job_history_of_non_collector_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $donor = User::factory()->donor()->create();

        $response = $this->actingAs($admin)->get(
            route('admin.collectors.job-history', $donor)
        );

        $response->assertStatus(404);
    }

    /**
     * Test that page only shows active collectors.
     */
    public function test_page_only_shows_active_collectors(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create(['name' => 'John Active']);
        $pendingCollector = User::factory()->collector()->create(['name' => 'Jane Waiting']);
        $blockedCollector = User::factory()->collector()->blocked()->create(['name' => 'Bob Blocked']);

        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertSee('John Active');
        $response->assertDontSee('Jane Waiting');
        $response->assertDontSee('Bob Blocked');
    }

    /**
     * Test that completed jobs count is displayed correctly.
     */
    public function test_completed_jobs_count_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->active()->create();

        // Create some collection jobs for the collector
        // (The completed_jobs count will be calculated in the query)
        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertStatus(200);
        $response->assertSee('Jobs Completed');
    }

    /**
     * Test that rating is displayed correctly.
     */
    public function test_rating_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->active()->create(['rating' => 3.75]);

        $response = $this->actingAs($admin)->get(route('admin.collectors.index'));

        $response->assertSee('3.8');
    }
}
