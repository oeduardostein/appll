<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_success_payload(): void
    {
        $payload = [
            'username' => 'john-doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertCreated();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure(['token', 'user' => ['id', 'username', 'email']]);

        $token = $response->json('token');
        $this->assertIsString($token);
        $hashedToken = hash('sha256', $token);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'john-doe',
            'api_token' => $hashedToken,
        ]);
    }

    public function test_login_accepts_username_or_email(): void
    {
        $user = User::factory()->create([
            'name' => 'maria',
            'email' => 'maria@example.com',
            'password' => 'password123',
        ]);

        $byEmail = $this->postJson('/api/auth/login', [
            'identifier' => $user->email,
            'password' => 'password123',
        ]);

        $byEmail->assertOk()->assertJsonPath('status', 'success');
        $emailToken = $byEmail->json('token');
        $this->assertIsString($emailToken);
        $this->assertSame(hash('sha256', $emailToken), $user->fresh()->api_token);

        $byUsername = $this->postJson('/api/auth/login', [
            'identifier' => $user->name,
            'password' => 'password123',
        ]);

        $byUsername->assertOk()->assertJsonPath('status', 'success');
        $usernameToken = $byUsername->json('token');
        $this->assertIsString($usernameToken);
        $this->assertSame(hash('sha256', $usernameToken), $user->fresh()->api_token);
    }

    public function test_logout_returns_success_payload(): void
    {
        $token = 'logout-token';
        $user = User::factory()->create([
            'api_token' => hash('sha256', $token),
        ]);

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso.',
            'redirect_to' => 'login',
        ]);
        $this->assertNull($user->fresh()->api_token);
    }

    public function test_logout_requires_valid_token(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
        $response->assertJsonPath('status', 'error');
    }

    public function test_current_returns_authenticated_user(): void
    {
        $token = 'current-token';
        $user = User::factory()->create([
            'api_token' => hash('sha256', $token),
        ]);

        $response = $this->getJson('/api/auth/user', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('user.id', $user->id);
    }

    public function test_current_requires_token(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
        $response->assertJsonPath('status', 'error');
    }
}
