<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle a registration request for the application.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário cadastrado com sucesso.',
            'user' => new UserResource($user),
            'redirect_to' => 'home',
        ], 201);
    }

    /**
     * Handle a login request to the application.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $identifier = $data['identifier'];
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $user = User::where($field, $identifier)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciais inválidas.',
                'errors' => [
                    'identifier' => ['Credenciais inválidas.'],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login realizado com sucesso.',
            'user' => new UserResource($user),
            'redirect_to' => 'home',
        ]);
    }
}
