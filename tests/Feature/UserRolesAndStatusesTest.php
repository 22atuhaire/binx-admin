<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRolesAndStatusesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test admin role creation and status.
     */
    public function test_admin_users_are_always_active(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertEquals(User::ROLE_ADMIN, $admin->role);
        $this->assertEquals(User::STATUS_ACTIVE, $admin->status);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isActive());
    }

    /**
     * Test donor role creation and status.
     */
    public function test_donor_users_are_active_immediately(): void
    {
        $donor = User::factory()->donor()->create();

        $this->assertEquals(User::ROLE_DONOR, $donor->role);
        $this->assertEquals(User::STATUS_ACTIVE, $donor->status);
        $this->assertTrue($donor->isDonor());
        $this->assertTrue($donor->isActive());
    }

    /**
     * Test collector role creation and status.
     */
    public function test_collector_users_start_as_pending(): void
    {
        $collector = User::factory()->collector()->create();

        $this->assertEquals(User::ROLE_COLLECTOR, $collector->role);
        $this->assertEquals(User::STATUS_PENDING, $collector->status);
        $this->assertTrue($collector->isCollector());
        $this->assertTrue($collector->isPending());
    }

    /**
     * Test role helper methods for admin.
     */
    public function test_admin_role_helper_methods(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isDonor());
        $this->assertFalse($admin->isCollector());
    }

    /**
     * Test role helper methods for donor.
     */
    public function test_donor_role_helper_methods(): void
    {
        $donor = User::factory()->donor()->create();

        $this->assertFalse($donor->isAdmin());
        $this->assertTrue($donor->isDonor());
        $this->assertFalse($donor->isCollector());
    }

    /**
     * Test role helper methods for collector.
     */
    public function test_collector_role_helper_methods(): void
    {
        $collector = User::factory()->collector()->create();

        $this->assertFalse($collector->isAdmin());
        $this->assertFalse($collector->isDonor());
        $this->assertTrue($collector->isCollector());
    }

    /**
     * Test status helper methods for active status.
     */
    public function test_active_status_helper_methods(): void
    {
        $user = User::factory()->donor()->create();

        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isPending());
        $this->assertFalse($user->isBlocked());
    }

    /**
     * Test status helper methods for pending status.
     */
    public function test_pending_status_helper_methods(): void
    {
        $user = User::factory()->collector()->create();

        $this->assertFalse($user->isActive());
        $this->assertTrue($user->isPending());
        $this->assertFalse($user->isBlocked());
    }

    /**
     * Test status helper methods for blocked status.
     */
    public function test_blocked_status_helper_methods(): void
    {
        $user = User::factory()->blocked()->create();

        $this->assertFalse($user->isActive());
        $this->assertFalse($user->isPending());
        $this->assertTrue($user->isBlocked());
    }

    /**
     * Test activating a pending collector.
     */
    public function test_can_activate_pending_collector(): void
    {
        $collector = User::factory()->collector()->create();
        $this->assertEquals(User::STATUS_PENDING, $collector->status);

        $collector->activate();
        $collector->refresh();

        $this->assertEquals(User::STATUS_ACTIVE, $collector->status);
        $this->assertTrue($collector->isActive());
    }

    /**
     * Test blocking a user.
     */
    public function test_can_block_user(): void
    {
        $user = User::factory()->donor()->create();
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);

        $user->block();
        $user->refresh();

        $this->assertEquals(User::STATUS_BLOCKED, $user->status);
        $this->assertTrue($user->isBlocked());
    }

    /**
     * Test setting user to pending.
     */
    public function test_can_set_user_to_pending(): void
    {
        $user = User::factory()->active()->create();
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);

        $user->setPending();
        $user->refresh();

        $this->assertEquals(User::STATUS_PENDING, $user->status);
        $this->assertTrue($user->isPending());
    }

    /**
     * Test creating user with specific role.
     */
    public function test_can_create_user_with_specific_role(): void
    {
        $adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertTrue($adminUser->isActive());
    }

    /**
     * Test all role constants are defined.
     */
    public function test_role_constants_are_defined(): void
    {
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('donor', User::ROLE_DONOR);
        $this->assertEquals('collector', User::ROLE_COLLECTOR);
    }

    /**
     * Test all status constants are defined.
     */
    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('pending', User::STATUS_PENDING);
        $this->assertEquals('active', User::STATUS_ACTIVE);
        $this->assertEquals('blocked', User::STATUS_BLOCKED);
    }

    /**
     * Test database has correct role values.
     */
    public function test_database_stores_correct_role_values(): void
    {
        $admin = User::factory()->admin()->create();
        $donor = User::factory()->donor()->create();
        $collector = User::factory()->collector()->create();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $donor->id,
            'role' => 'donor',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $collector->id,
            'role' => 'collector',
        ]);
    }

    /**
     * Test database has correct status values.
     */
    public function test_database_stores_correct_status_values(): void
    {
        $activeUser = User::factory()->active()->create();
        $pendingUser = User::factory()->pending()->create();
        $blockedUser = User::factory()->blocked()->create();

        $this->assertDatabaseHas('users', [
            'id' => $activeUser->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $blockedUser->id,
            'status' => 'blocked',
        ]);
    }

    /**
     * Test collector can be activated.
     */
    public function test_collector_activation_workflow(): void
    {
        // Create pending collector
        $collector = User::factory()->collector()->create();

        $this->assertTrue($collector->isCollector());
        $this->assertTrue($collector->isPending());

        // Activate collector
        $collector->activate();
        $collector->refresh();

        $this->assertTrue($collector->isActive());
        $this->assertFalse($collector->isPending());

        $this->assertDatabaseHas('users', [
            'id' => $collector->id,
            'status' => 'active',
        ]);
    }
}
