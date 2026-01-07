<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Traits\FindsUserFromApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use FindsUserFromApiToken;

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
            'privacy_policy_accepted_at' => now(),
        ]);

        $token = $this->issueToken($user, $request);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário cadastrado com sucesso.',
            'user' => new UserResource($user),
            'token' => $token,
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

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sua conta está inativa. Entre em contato com o suporte.',
                'errors' => [
                    'identifier' => ['Conta inativa.'],
                ],
            ], 403);
        }

        $token = $this->issueToken($user, $request);

        return response()->json([
            'status' => 'success',
            'message' => 'Login realizado com sucesso.',
            'user' => new UserResource($user),
            'token' => $token,
            'redirect_to' => 'home',
        ]);
    }

    /**
     * Return the authenticated user information.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $this->findUserFromRequest($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        return response()->json([
            'status' => 'success',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Handle a logout request from the application.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $this->findUserFromRequest($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if ($token = $this->extractTokenFromRequest($request)) {
            ApiToken::where('token', hash('sha256', $token))->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso.',
            'redirect_to' => 'login',
        ]);
    }

    private function issueToken(User $user, Request $request): string
    {
        $plainTextToken = Str::random(60);

        ApiToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_used_at' => now(),
        ]);

        return $plainTextToken;
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Não autenticado.',
        ], 401);
    }
}
