<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\FindsUserFromApiToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    use FindsUserFromApiToken;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->findUserFromRequest($request, ['permissions:id,slug']);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não autenticado.',
            ], 401);
        }

        $permissions = $user->permissions()
            ->select('permissions.id', 'permissions.name', 'permissions.slug')
            ->get()
            ->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'permissions' => $permissions,
            'slugs' => $permissions->pluck('slug')->filter()->values(),
        ]);
    }

}
