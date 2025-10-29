<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

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
                'stats' => $this->buildStats(),
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

    /**
     * @return array<string, int>
     */
    private function buildStats(): array
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
