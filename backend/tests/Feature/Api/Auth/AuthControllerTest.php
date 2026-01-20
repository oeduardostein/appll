<?php

namespace Tests\Feature\Api\Auth;

use App\Models\ApiToken;
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
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'token' => $hashedToken,
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
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $emailToken),
        ]);
        $this->assertSame(1, ApiToken::where('user_id', $user->id)->count());

        $byUsername = $this->postJson('/api/auth/login', [
            'identifier' => $user->name,
            'password' => 'password123',
        ]);

        $byUsername->assertOk()->assertJsonPath('status', 'success');
        $usernameToken = $byUsername->json('token');
        $this->assertIsString($usernameToken);
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $usernameToken),
        ]);
        $this->assertSame(2, ApiToken::where('user_id', $user->id)->count());
    }

    public function test_logout_returns_success_payload(): void
    {
        $token = 'logout-token';
        $user = User::factory()->create();
        ApiToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
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
        $this->assertDatabaseMissing('api_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
        ]);
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
        $user = User::factory()->create();
        ApiToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
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
