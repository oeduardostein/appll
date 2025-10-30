<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin()
    {
        return $this->withSession([
            'admin_authenticated' => true,
            'admin_user' => ['name' => 'Admin', 'role' => 'Administrador'],
        ]);
    }

    public function test_it_updates_status_in_bulk(): void
    {
        $activeUsers = User::factory()->count(2)->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $response = $this->actingAsAdmin()->postJson(route('admin.users.bulk-status'), [
            'user_ids' => array_merge($activeUsers->pluck('id')->all(), [9999]),
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('processed_ids.0', $activeUsers->first()->id);
        $this->assertDatabaseHas('users', [
            'id' => $activeUsers->first()->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $inactiveUser->id,
            'is_active' => false,
        ]);
    }

    public function test_it_deletes_users_in_bulk(): void
    {
        $users = User::factory()->count(3)->create();

        $response = $this->actingAsAdmin()->deleteJson(route('admin.users.bulk-destroy'), [
            'user_ids' => array_merge($users->take(2)->pluck('id')->all(), [12345]),
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'processed_ids');
        $this->assertDatabaseMissing('users', ['id' => $users[0]->id]);
        $this->assertDatabaseMissing('users', ['id' => $users[1]->id]);
        $this->assertDatabaseHas('users', ['id' => $users[2]->id]);
    }
}
