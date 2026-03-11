<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCollectorApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin can list pending collectors.
     */
    public function test_admin_can_list_pending_collectors(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->collector()->count(2)->create();
        User::factory()->collector()->active()->create();

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/collectors/pending');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Non-admin cannot list pending collectors.
     */
    public function test_non_admin_cannot_list_pending_collectors(): void
    {
        $donor = User::factory()->donor()->create();
        $token = $donor->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/collectors/pending');

        $response->assertForbidden();
    }

    /**
     * Admin can approve a pending collector.
     */
    public function test_admin_can_approve_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$collector->id}/approve");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Collector approved successfully.',
            'user' => [
                'id' => $collector->id,
                'status' => User::STATUS_ACTIVE,
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $collector->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * Admin cannot approve collector that is already active.
     */
    public function test_admin_cannot_approve_non_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $activeCollector = User::factory()->collector()->active()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$activeCollector->id}/approve");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Collector is not pending approval.',
        ]);
    }

    /**
     * Admin cannot approve a donor account.
     */
    public function test_admin_cannot_approve_non_collector_user(): void
    {
        $admin = User::factory()->admin()->create();
        $donor = User::factory()->donor()->pending()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$donor->id}/approve");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'User is not a collector.',
        ]);
    }

    /**
     * Admin can reject a pending collector with reason.
     */
    public function test_admin_can_reject_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$collector->id}/reject", [
                'reason' => 'Invalid documents provided for verification.',
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Collector rejected successfully.',
            'user' => [
                'id' => $collector->id,
                'status' => User::STATUS_BLOCKED,
                'rejection_reason' => 'Invalid documents provided for verification.',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $collector->id,
            'status' => User::STATUS_BLOCKED,
            'rejection_reason' => 'Invalid documents provided for verification.',
        ]);
    }

    /**
     * Reject requires a reason.
     */
    public function test_reject_requires_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$collector->id}/reject", [
                'reason' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    /**
     * Reject requires reason with minimum length.
     */
    public function test_reject_requires_minimum_reason_length(): void
    {
        $admin = User::factory()->admin()->create();
        $collector = User::factory()->collector()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$collector->id}/reject", [
                'reason' => 'Short',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    /**
     * Admin cannot reject already rejected collector.
     */
    public function test_admin_cannot_reject_non_pending_collector(): void
    {
        $admin = User::factory()->admin()->create();
        $blockedCollector = User::factory()->collector()->blocked()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$blockedCollector->id}/reject", [
                'reason' => 'This collector is already rejected.',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Collector is not pending approval.',
        ]);
    }

    /**
     * Non-admin cannot reject collector.
     */
    public function test_non_admin_cannot_reject_collector(): void
    {
        $donor = User::factory()->donor()->create();
        $collector = User::factory()->collector()->create();
        $token = $donor->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/collectors/{$collector->id}/reject", [
                'reason' => 'Invalid documents provided.',
            ]);

        $response->assertForbidden();
    }
}
