<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Donor',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'donor',
            'location' => 'Kampala, Uganda',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'role'],
            'token',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'donor',
        ]);
    }

    public function test_register_requires_valid_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'donor',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'different',
            'role' => 'donor',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_register_with_confirm_password_field(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Donor',
            'email' => 'john.confirm@example.com',
            'password' => 'Password123!',
            'confirm_password' => 'Password123!',
            'role' => 'donor',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'role'],
            'token',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.confirm@example.com',
            'role' => 'donor',
        ]);
    }

    public function test_register_requires_strong_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'role' => 'donor',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'donor',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_blocked_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
            'status' => User::STATUS_BLOCKED,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Your account has been blocked']);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Verify token works before logout
        $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/me')
            ->assertOk();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $response->assertJson(['message' => 'Logout successful']);

        // Verify token is deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'test',
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Verify token works before refresh
        $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/me')
            ->assertOk();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/refresh');

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'token',
        ]);

        $newToken = $response->json('token');

        // Old token should be invalidated
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'token' => hash('sha256', $token),
        ]);

        // New token should work
        $this->withHeader('Authorization', "Bearer $newToken")
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_user_can_get_current_user(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/me');

        $response->assertOk();
        $response->assertJson([
            'user' => [
                'id' => $user->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);
    }

    public function test_get_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_multiple_logins_revoke_previous_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // First login
        $response1 = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);
        $token1 = $response1->json('token');

        // Second login
        $response2 = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);
        $token2 = $response2->json('token');

        // First token should be invalidated
        $this->withHeader('Authorization', "Bearer $token1")
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        // Second token should be valid
        $this->withHeader('Authorization', "Bearer $token2")
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_collector_can_register_without_email(): void
    {
        $response = $this->postJson('/api/collector/register', [
            'name' => 'John Collector',
            'phone' => '0700123456',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'vehicle_type' => 'Truck',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'user' => ['id', 'name', 'phone', 'role', 'vehicle_type', 'status'],
        ]);

        $this->assertDatabaseHas('users', [
            'phone' => '0700123456',
            'role' => User::ROLE_COLLECTOR,
            'vehicle_type' => 'Truck',
            'status' => User::STATUS_PENDING,
            'email' => null,
        ]);
    }

    public function test_collector_can_register_with_email(): void
    {
        $response = $this->postJson('/api/collector/register', [
            'name' => 'John Collector',
            'phone' => '0700123456',
            'email' => 'collector@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'vehicle_type' => 'Truck',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'user',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'collector@example.com',
            'phone' => '0700123456',
            'role' => User::ROLE_COLLECTOR,
        ]);
    }
+
    public function test_collector_can_login_via_collector_login_endpoint(): void
    {
        User::factory()->create([
            'phone' => '0700123456',
            'email' => null,
            'password' => Hash::make('Password123!'),
            'role' => User::ROLE_COLLECTOR,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/collector/login', [
            'phone' => '0700123456',
            'password' => 'Password123!',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'phone', 'role'],
            'token',
        ]);
    }

    public function test_non_collector_cannot_login_via_collector_login_endpoint(): void
    {
        User::factory()->create([
            'phone' => '0700999888',
            'email' => 'donor@example.com',
            'password' => Hash::make('Password123!'),
            'role' => User::ROLE_DONOR,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/collector/login', [
            'phone' => '0700999888',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Only collectors can log in here']);
    }

    public function test_user_can_login_with_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '0700123456',
            'email' => null,
            'password' => Hash::make('Password123!'),
            'role' => User::ROLE_COLLECTOR,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '0700123456',
            'password' => 'Password123!',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'phone'],
            'token',
        ]);
    }

    public function test_login_with_phone_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'phone' => '0700123456',
            'email' => null,
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '0700123456',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_requires_email_or_phone(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_collector_register_requires_password(): void
    {
        $response = $this->postJson('/api/collector/register', [
            'name' => 'John Collector',
            'phone' => '0700123456',
            'vehicle_type' => 'Truck',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }
}
