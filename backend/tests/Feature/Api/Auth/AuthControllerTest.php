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
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'john-doe',
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

        $byUsername = $this->postJson('/api/auth/login', [
            'identifier' => $user->name,
            'password' => 'password123',
        ]);

        $byUsername->assertOk()->assertJsonPath('status', 'success');
    }

    public function test_logout_returns_success_payload(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso.',
            'redirect_to' => 'login',
        ]);
    }
}
