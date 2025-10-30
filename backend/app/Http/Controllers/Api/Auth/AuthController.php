<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        $token = $this->issueToken($user);

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

        $token = $this->issueToken($user);

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

        if (! $user) {
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

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        $user->forceFill(['api_token' => null])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso.',
            'redirect_to' => 'login',
        ]);
    }

    private function issueToken(User $user): string
    {
        $plainTextToken = Str::random(60);

        $user->forceFill([
            'api_token' => hash('sha256', $plainTextToken),
        ])->save();

        return $plainTextToken;
    }

    private function findUserFromRequest(Request $request): ?User
    {
        $token = $this->extractTokenFromRequest($request);

        if (! $token) {
            return null;
        }

        return User::where('api_token', hash('sha256', $token))->first();
    }

    private function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');

        if (is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if ($token !== '') {
                return $token;
            }
        }

        $token = $request->input('token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        return null;
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Não autenticado.',
        ], 401);
    }
}
