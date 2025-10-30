<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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

        if ($appliedFilters['credits_min'] !== '') {
            $usersQuery->where('credits', '>=', (int) $appliedFilters['credits_min']);
        }

        if ($appliedFilters['credits_max'] !== '') {
            $usersQuery->where('credits', '<=', (int) $appliedFilters['credits_max']);
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
            'credits' => ['required', 'integer', 'min:0'],
            'last_login_at' => ['nullable', 'date'],
        ]);

        $user = new User();
        $user->fill(Arr::only($validated, ['name', 'email', 'is_active', 'credits', 'last_login_at']));
        $user->password = $validated['password'];
        $user->save();

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
            'credits' => ['required', 'integer', 'min:0'],
            'last_login_at' => ['nullable', 'date'],
        ]);

        $payload = Arr::only($validated, ['name', 'email', 'is_active', 'credits', 'last_login_at']);

        $user->fill($payload);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

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
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $updated = User::query()
            ->whereIn('id', $validated['user_ids'])
            ->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'Status atualizado para os usuários selecionados.',
            'updated' => $updated,
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $deleted = User::query()
            ->whereIn('id', $validated['user_ids'])
            ->delete();

        return response()->json([
            'message' => 'Usuários removidos com sucesso.',
            'deleted' => $deleted,
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
     *     created_to: string,
     *     credits_min: string,
     *     credits_max: string
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
            'credits_min' => $this->normalizeInteger($request->query('credits_min')),
            'credits_max' => $this->normalizeInteger($request->query('credits_max')),
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

    private function normalizeInteger(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);

        if ($int === false) {
            return '';
        }

        return (string) $int;
    }
}
