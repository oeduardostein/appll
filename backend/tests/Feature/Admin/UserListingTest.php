<?php

namespace Tests\Feature\Admin;

use App\Models\Pesquisa;
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
    }

    public function test_it_filters_users_by_status_and_search(): void
    {
        User::factory()->create([
            'name' => 'Alice Active',
            'email' => 'alice@example.com',
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'name' => 'Bob Inactive',
            'email' => 'bob@example.com',
            'is_active' => false,
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
            'created_from' => 'invalid-date',
            'created_to' => '2024-13-01',
        ]);

        $response->assertOk();
        $response->assertJsonPath('meta.filters.status', 'all');
        $response->assertJsonPath('meta.filters.created_from', '');
        $response->assertJsonPath('meta.filters.created_to', '');
    }

    public function test_it_returns_total_credits_used_from_pesquisas(): void
    {
        $user = User::factory()->create([
            'name' => 'Consumidor de Créditos',
            'email' => 'consumidor@example.com',
        ]);

        Pesquisa::query()->create([
            'user_id' => $user->id,
            'nome' => 'Consulta 1',
            'placa' => 'AAA1A11',
            'renavam' => '12345678901',
            'chassi' => '1HGBH41JXMN109186',
            'opcao_pesquisa' => 'placa',
        ]);

        Pesquisa::query()->create([
            'user_id' => $user->id,
            'nome' => 'Consulta 2',
            'placa' => 'BBB2B22',
            'renavam' => '10987654321',
            'chassi' => '1HGCM82633A004352',
            'opcao_pesquisa' => 'renavam',
        ]);

        $response = $this->getAdminUsers();

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $user->id);
        $response->assertJsonPath('data.0.credits_used', 2);
        $response->assertJsonPath('data.0.credits_used_label', '2 créditos utilizados');
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
