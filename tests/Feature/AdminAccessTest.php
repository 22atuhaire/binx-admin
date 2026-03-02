<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access admin routes.
     */
    public function test_guests_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that non-admin users cannot access admin routes.
     */
    public function test_non_admin_users_cannot_access_admin_dashboard(): void
    {
        // Create a donor (non-admin) user
        $donor = User::factory()->donor()->create();

        $response = $this->actingAs($donor)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    /**
     * Test that collectors cannot access admin routes.
     */
    public function test_collectors_cannot_access_admin_dashboard(): void
    {
        // Create a collector (non-admin) user
        $collector = User::factory()->collector()->create();

        $response = $this->actingAs($collector)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    /**
     * Test that admin users can access admin dashboard.
     */
    public function test_admin_users_can_access_admin_dashboard(): void
    {
        // Create an admin user
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }

    /**
     * Test that admin users can access pending collectors page.
     */
    public function test_admin_can_access_pending_collectors_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.collectors.pending'));

        $response->assertStatus(200);
        $response->assertSee('Pending Collectors');
    }

    /**
     * Test that admin users can access all users page.
     */
    public function test_admin_can_access_all_users_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('All Users');
    }

    /**
     * Test that admin can approve a pending collector.
     */
    public function test_admin_can_approve_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->create(); // Creates pending collector

        $this->assertTrue($collector->isPending());

        $response = $this->actingAs($admin)
            ->post(route('admin.collectors.approve', $collector));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $collector->refresh();
        $this->assertTrue($collector->isActive());
    }

    /**
     * Test that admin can block a user.
     */
    public function test_admin_can_block_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->donor()->create();

        $this->assertTrue($user->isActive());

        $response = $this->actingAs($admin)
            ->post(route('admin.users.block', $user));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->isBlocked());
    }

    /**
     * Test that admin can activate a blocked user.
     */
    public function test_admin_can_activate_blocked_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->blocked()->create();

        $this->assertTrue($user->isBlocked());

        $response = $this->actingAs($admin)
            ->post(route('admin.users.activate', $user));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->isActive());
    }

    /**
     * Test that non-admin cannot approve collectors.
     */
    public function test_non_admin_cannot_approve_collectors(): void
    {
        $donor = User::factory()->donor()->create();
        $collector = User::factory()->collector()->create();

        $response = $this->actingAs($donor)
            ->post(route('admin.collectors.approve', $collector));

        $response->assertStatus(403);

        $collector->refresh();
        $this->assertTrue($collector->isPending());
    }
}
