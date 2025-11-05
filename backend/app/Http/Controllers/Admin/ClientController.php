<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $perPage = 10;

        $users = User::query()
            ->withCount(['pesquisas as credits_used'])
            ->latest('created_at')
            ->paginate($perPage);

        $metrics = $this->buildMetrics();

        /** @var Collection<int, array<string, mixed>> $initialUsers */
        $initialUsers = UserResource::collection($users->getCollection())->resolve();

        $statCards = [
            [
                'key' => 'active',
                'title' => 'Usuários Ativos',
                'value' => sprintf('%d usuários', $metrics['active']),
                'trend' => null,
            ],
            [
                'key' => 'new_this_month',
                'title' => 'Novos Usuários (mês)',
                'value' => sprintf('%d usuários', $metrics['new_this_month']),
                'trend' => null,
            ],
            [
                'key' => 'inactive',
                'title' => 'Usuários Inativos',
                'value' => sprintf('%d usuários', $metrics['inactive']),
                'trend' => null,
            ],
        ];

        return view('admin.clients.index', [
            'stats' => $statCards,
            'selectedCount' => 0,
            'initialUsers' => $initialUsers,
            'initialMetrics' => $metrics,
            'initialPagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function buildMetrics(): array
    {
        $total = User::query()->count();
        $active = User::query()->where('is_active', true)->count();
        $inactive = User::query()->where('is_active', false)->count();
        $newThisMonth = User::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'new_this_month' => $newThisMonth,
        ];
    }
}
