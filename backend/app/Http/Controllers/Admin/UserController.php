<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $search = trim((string) $request->query('search'));

        $usersQuery = User::query();
        $appliedFilters = $this->resolveFilters($request);

        if ($appliedFilters['status'] !== 'all') {
            $usersQuery->where('is_active', $appliedFilters['status'] === 'active');
        }

        if ($appliedFilters['created_from'] !== '') {
            $usersQuery->whereDate('created_at', '>=', Carbon::parse($appliedFilters['created_from'])->startOfDay());
        }

        if ($appliedFilters['created_to'] !== '') {
            $usersQuery->whereDate('created_at', '<=', Carbon::parse($appliedFilters['created_to'])->endOfDay());
        }

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $stats = $this->buildStats(clone $usersQuery);

        $users = $usersQuery
            ->with([
                'permissions:id,name,slug',
            ])
            ->withCount(['pesquisas as credits_used'])
            ->latest('created_at')
            ->paginate($perPage);

        $resource = UserResource::collection($users);
        $resource->additional([
            'meta' => [
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
                'stats' => $stats,
                'filters' => $appliedFilters,
            ],
        ]);

        return $resource->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
            'last_login_at' => ['nullable', 'date'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
            'permission_credit_values' => ['nullable', 'array'],
            'permission_credit_values.*' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $user = new User();
        $user->fill(Arr::only($validated, ['name', 'email', 'is_active', 'last_login_at']));
        $user->password = $validated['password'];
        $user->save();
        $this->syncPermissions($user, $validated['permissions'] ?? [], $validated['permission_credit_values'] ?? []);
        $user->load('permissions');

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
            'last_login_at' => ['nullable', 'date'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
            'permission_credit_values' => ['nullable', 'array'],
            'permission_credit_values.*' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'redirect_to' => ['nullable', 'url'],
        ]);

        $payload = Arr::only($validated, ['name', 'email', 'is_active', 'last_login_at']);

        $user->fill($payload);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        $this->syncPermissions($user, $validated['permissions'] ?? [], $validated['permission_credit_values'] ?? []);
        $user->load('permissions');

        if ($redirect = $validated['redirect_to'] ?? null) {
            if (! Str::startsWith($redirect, url('/'))) {
                $redirect = route('admin.clients.show', $user);
            }

            return redirect($redirect)->with('status', 'Usuário atualizado com sucesso.');
        }

        return (new UserResource($user))->response();
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuário removido com sucesso.',
        ], Response::HTTP_OK);
    }

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct'],
            'is_active' => ['required', 'boolean'],
        ]);

        $ids = collect($validated['user_ids'])
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum usuário válido informado.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $updated = User::query()
            ->whereIn('id', $ids)
            ->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'Status atualizado para os usuários selecionados.',
            'updated' => $updated,
            'processed_ids' => $ids,
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct'],
        ]);

        $ids = collect($validated['user_ids'])
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum usuário válido informado.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $deleted = User::query()
            ->whereIn('id', $ids)
            ->delete();

        return response()->json([
            'message' => 'Usuários removidos com sucesso.',
            'deleted' => $deleted,
            'processed_ids' => $ids,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function buildStats(?Builder $baseQuery = null): array
    {
        $query = $baseQuery ? (clone $baseQuery) : User::query();

        $total = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();
        $inactive = (clone $query)->where('is_active', false)->count();
        $newThisMonth = (clone $query)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'new_this_month' => $newThisMonth,
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     created_from: string,
     *     created_to: string
     * }
     */
    private function resolveFilters(Request $request): array
    {
        $status = strtolower((string) $request->query('status', ''));
        $status = in_array($status, ['active', 'inactive'], true) ? $status : 'all';

        return [
            'status' => $status,
            'created_from' => $this->normalizeDate($request->query('created_from')),
            'created_to' => $this->normalizeDate($request->query('created_to')),
        ];
    }

    private function normalizeDate(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param array<int, int|string> $permissionIds
     * @param array<int|string, mixed> $permissionValues
     */
    private function syncPermissions(User $user, array $permissionIds, array $permissionValues = []): void
    {
        $ids = collect($permissionIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            $user->permissions()->sync([]);

            return;
        }

        $valueMap = collect($permissionValues)
            ->mapWithKeys(static function ($value, $key): array {
                $id = (int) $key;
                if ($id <= 0) {
                    return [];
                }

                if ($value === null || $value === '') {
                    return [];
                }

                return [$id => round((float) $value, 2)];
            });

        $defaults = Permission::query()
            ->whereIn('id', $ids)
            ->pluck('default_credit_value', 'id');

        $payload = [];
        foreach ($ids as $id) {
            $payload[$id] = [
                'credit_value' => $valueMap->get($id, $defaults->get($id)),
            ];
        }

        $user->permissions()->sync($payload);
    }
}
