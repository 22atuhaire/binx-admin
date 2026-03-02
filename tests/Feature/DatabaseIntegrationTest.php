<?php

namespace Tests\Feature;

use App\Models\CollectionJob;
use App\Models\Earning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DatabaseIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test creating a new user donor.
     */
    public function test_create_user_donor(): void
    {
        $user = User::create([
            'name' => 'Test Donor',
            'email' => 'donor@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'phone' => '555-1234',
            'address' => '123 Test St',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Test Donor',
            'role' => 'user',
        ]);
    }

    /**
     * Test creating a collector user.
     */
    public function test_create_collector_user(): void
    {
        $collector = User::factory()->collector()->create();

        $this->assertDatabaseHas('users', [
            'id' => $collector->id,
            'role' => 'collector',
        ]);
    }

    /**
     * Test creating a waste post.
     */
    public function test_create_waste_post(): void
    {
        $user = User::factory()->regularUser()->create();

        $post = $user->wastePosts()->create([
            'title' => 'Test Waste Post',
            'description' => 'Test description',
            'category' => 'organic',
            'location' => 'Test Location',
            'quantity' => '10kg',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('waste_posts', [
            'id' => $post->id,
            'user_id' => $user->id,
            'title' => 'Test Waste Post',
        ]);
    }

    /**
     * Test creating a collection job.
     */
    public function test_create_collection_job(): void
    {
        $user = User::factory()->regularUser()->create();
        $collector = User::factory()->collector()->create();
        $post = $user->wastePosts()->create([
            'title' => 'Test Post',
            'description' => 'Description',
            'category' => 'plastic',
            'location' => 'Location',
            'quantity' => '5kg',
            'status' => 'open',
        ]);

        $job = CollectionJob::create([
            'waste_post_id' => $post->id,
            'collector_id' => $collector->id,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $this->assertDatabaseHas('collection_jobs', [
            'id' => $job->id,
            'waste_post_id' => $post->id,
            'collector_id' => $collector->id,
        ]);
    }

    /**
     * Test creating an earning record.
     */
    public function test_create_earning(): void
    {
        $collector = User::factory()->collector()->create();
        $user = User::factory()->regularUser()->create();
        $post = $user->wastePosts()->create([
            'title' => 'Test Post',
            'description' => 'Description',
            'category' => 'metal',
            'location' => 'Location',
            'quantity' => '8kg',
            'status' => 'open',
        ]);
        $job = CollectionJob::create([
            'waste_post_id' => $post->id,
            'collector_id' => $collector->id,
            'status' => 'completed',
            'assigned_at' => now(),
            'completed_at' => now(),
        ]);

        $earning = Earning::create([
            'collector_id' => $collector->id,
            'job_id' => $job->id,
            'amount' => 50.00,
            'description' => 'Test earning',
            'earned_at' => now(),
        ]);

        $this->assertDatabaseHas('earnings', [
            'id' => $earning->id,
            'collector_id' => $collector->id,
            'job_id' => $job->id,
            'amount' => 50.00,
        ]);
    }

    /**
     * Test reading user with relationships.
     */
    public function test_read_user_with_relationships(): void
    {
        $user = User::factory()->regularUser()->create();
        $user->wastePosts()->create([
            'title' => 'Post 1',
            'description' => 'Desc 1',
            'category' => 'paper',
            'location' => 'Loc 1',
            'quantity' => '3kg',
            'status' => 'open',
        ]);

        $foundUser = User::with('wastePosts')->find($user->id);

        $this->assertEquals('user', $foundUser->role);
        $this->assertEquals($foundUser->wastePosts->count(), 1);
    }

    /**
     * Test updating user profile.
     */
    public function test_update_user_profile(): void
    {
        $user = User::factory()->create();
        $originalPhone = $user->phone;

        $user->update([
            'phone' => '555-9999',
            'rating' => 4.5,
        ]);

        $this->assertNotEquals($originalPhone, $user->phone);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '555-9999',
            'rating' => 4.5,
        ]);
    }

    /**
     * Test updating waste post status.
     */
    public function test_update_waste_post_status(): void
    {
        $user = User::factory()->regularUser()->create();
        $post = $user->wastePosts()->create([
            'title' => 'Test Post',
            'description' => 'Description',
            'category' => 'glass',
            'location' => 'Location',
            'quantity' => '2kg',
            'status' => 'open',
        ]);

        $post->update(['status' => 'taken']);

        $this->assertDatabaseHas('waste_posts', [
            'id' => $post->id,
            'status' => 'taken',
        ]);
    }

    /**
     * Test updating collection job status.
     */
    public function test_update_collection_job_status(): void
    {
        $user = User::factory()->regularUser()->create();
        $collector = User::factory()->collector()->create();
        $post = $user->wastePosts()->create([
            'title' => 'Test Post',
            'description' => 'Description',
            'category' => 'organic',
            'location' => 'Location',
            'quantity' => '7kg',
            'status' => 'open',
        ]);
        $job = CollectionJob::create([
            'waste_post_id' => $post->id,
            'collector_id' => $collector->id,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->assertDatabaseHas('collection_jobs', [
            'id' => $job->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Test deleting a waste post.
     */
    public function test_delete_waste_post(): void
    {
        $user = User::factory()->regularUser()->create();
        $post = $user->wastePosts()->create([
            'title' => 'Post to Delete',
            'description' => 'Description',
            'category' => 'textile',
            'location' => 'Location',
            'quantity' => '4kg',
            'status' => 'open',
        ]);

        $postId = $post->id;
        $post->delete();

        $this->assertDatabaseMissing('waste_posts', ['id' => $postId]);
    }

    /**
     * Test collector job count aggregation.
     */
    public function test_collector_job_count(): void
    {
        $collector = User::factory()->collector()->create();

        CollectionJob::factory(3)
            ->for($collector, 'collector')
            ->create();

        $this->assertEquals(
            CollectionJob::where('collector_id', $collector->id)->count(),
            3
        );
    }

    /**
     * Test total earnings calculation.
     */
    public function test_total_earnings_calculation(): void
    {
        $collector = User::factory()->collector()->create();

        Earning::factory(5)
            ->for($collector, 'collector')
            ->create();

        $total = Earning::where('collector_id', $collector->id)->sum('amount');

        $this->assertGreaterThan(0, $total);
    }

    /**
     * Test cascade delete when user is deleted.
     */
    public function test_cascade_delete_waste_posts(): void
    {
        $user = User::factory()->regularUser()->create();
        $user->wastePosts()->create([
            'title' => 'Post 1',
            'description' => 'Desc 1',
            'category' => 'paper',
            'location' => 'Loc 1',
            'quantity' => '1kg',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('waste_posts', [
            'user_id' => $user->id,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('waste_posts', ['user_id' => $user->id]);
    }

    /**
     * Test relationship between collection job and earning.
     */
    public function test_collection_job_earning_relationship(): void
    {
        $collector = User::factory()->collector()->create();
        $user = User::factory()->regularUser()->create();
        $post = $user->wastePosts()->create([
            'title' => 'Test Post',
            'description' => 'Description',
            'category' => 'electronics',
            'location' => 'Location',
            'quantity' => '6kg',
            'status' => 'open',
        ]);
        $job = CollectionJob::create([
            'waste_post_id' => $post->id,
            'collector_id' => $collector->id,
            'status' => 'completed',
            'assigned_at' => now(),
            'completed_at' => now(),
        ]);

        $earning = Earning::create([
            'collector_id' => $collector->id,
            'job_id' => $job->id,
            'amount' => 75.50,
            'description' => 'Payment',
            'earned_at' => now(),
        ]);

        $fetchedJob = CollectionJob::with('earning')->find($job->id);

        $this->assertNotNull($fetchedJob->earning);
        $this->assertEquals($fetchedJob->earning->amount, 75.50);
    }
}
