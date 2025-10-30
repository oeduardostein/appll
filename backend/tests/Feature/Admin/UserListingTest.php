<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserListingTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUsers(array $params = [])
    {
        $uri = route('admin.users.index', $params);

        return $this->withSession([
            'admin_authenticated' => true,
            'admin_user' => ['name' => 'Admin', 'role' => 'Administrador'],
        ])->getJson($uri);
    }

    private function updateCreatedAt(User $user, Carbon $date): void
    {
        User::query()
            ->whereKey($user->id)
            ->update([
                'created_at' => $date,
                'updated_at' => $date,
            ]);

        $user->refresh();
    }

    public function test_it_returns_users_with_default_filters(): void
    {
        User::factory()->count(3)->create();

        $response = $this->getAdminUsers();

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('meta.pagination.total', 3);
        $response->assertJsonPath('meta.filters.status', 'all');
        $response->assertJsonPath('meta.filters.credits_min', '');
        $response->assertJsonPath('meta.filters.credits_max', '');
    }

    public function test_it_filters_users_by_status_and_search(): void
    {
        User::factory()->create([
            'name' => 'Alice Active',
            'email' => 'alice@example.com',
            'is_active' => true,
            'credits' => 10,
        ]);

        $target = User::factory()->create([
            'name' => 'Bob Inactive',
            'email' => 'bob@example.com',
            'is_active' => false,
            'credits' => 20,
        ]);

        $response = $this->getAdminUsers([
            'search' => 'bob',
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $target->id);
        $response->assertJsonPath('meta.filters.status', 'inactive');
        $response->assertJsonPath('meta.stats.active', 0);
        $response->assertJsonPath('meta.stats.inactive', 1);
    }

    public function test_it_filters_users_by_credit_range(): void
    {
        User::factory()->create([
            'name' => 'Credit Low',
            'email' => 'credits-low@example.com',
            'credits' => 5,
        ]);

        $match = User::factory()->create([
            'name' => 'Credit Mid',
            'email' => 'credits-mid@example.com',
            'credits' => 15,
        ]);

        User::factory()->create([
            'name' => 'Credit High',
            'email' => 'credits-high@example.com',
            'credits' => 30,
        ]);

        $response = $this->getAdminUsers([
            'credits_min' => 10,
            'credits_max' => 20,
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
        $response->assertJsonPath('meta.filters.credits_min', '10');
        $response->assertJsonPath('meta.filters.credits_max', '20');
    }

    public function test_it_filters_users_by_created_date_range(): void
    {
        $inside = User::factory()->create([
            'name' => 'Inside Window',
            'email' => 'inside@example.com',
        ]);

        $outside = User::factory()->create([
            'name' => 'Outside Window',
            'email' => 'outside@example.com',
        ]);

        $this->updateCreatedAt($inside, Carbon::create(2024, 5, 10, 12));
        $this->updateCreatedAt($outside, Carbon::create(2024, 3, 5, 12));

        $response = $this->getAdminUsers([
            'created_from' => '2024-05-01',
            'created_to' => '2024-05-31',
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $inside->id);
        $response->assertJsonPath('meta.filters.created_from', '2024-05-01');
        $response->assertJsonPath('meta.filters.created_to', '2024-05-31');
    }

    public function test_it_sanitizes_invalid_filter_values(): void
    {
        User::factory()->create();

        $response = $this->getAdminUsers([
            'status' => 'unknown',
            'credits_min' => 'abc',
            'credits_max' => '9.5',
            'created_from' => 'invalid-date',
            'created_to' => '2024-13-01',
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.filters.status', 'all');
        $response->assertJsonPath('meta.filters.credits_min', '');
        $response->assertJsonPath('meta.filters.credits_max', '');
        $response->assertJsonPath('meta.filters.created_from', '');
        $response->assertJsonPath('meta.filters.created_to', '');
    }

    public function test_it_limits_per_page_between_one_and_fifty(): void
    {
        User::factory()->count(60)->create();

        $response = $this->getAdminUsers(['per_page' => 100]);
        $response->assertOk();
        $response->assertJsonPath('meta.pagination.per_page', 50);

        $response = $this->getAdminUsers(['per_page' => 0]);
        $response->assertOk();
        $response->assertJsonPath('meta.pagination.per_page', 1);
    }
}
