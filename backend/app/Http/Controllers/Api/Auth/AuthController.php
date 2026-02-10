<?php

namespace App\Http\Controllers\Api\Auth;

use App\Mail\LoginSecurityKeyMail;
use App\Http\Controllers\Api\Traits\FindsUserFromApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LoginVerifyRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $rememberMe = filter_var($request->input('remember_me', false), FILTER_VALIDATE_BOOLEAN);
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

        $challengeId = Str::random(60);
        $securityKey = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        $user->forceFill([
            'login_security_challenge' => $challengeId,
            'login_security_key_hash' => hash('sha256', $securityKey),
            'login_security_key_expires_at' => $expiresAt,
            'login_security_key_sent_at' => now(),
        ])->save();

        try {
            Mail::to($user->email)->send(new LoginSecurityKeyMail($securityKey, $expiresAt));
        } catch (\Throwable $e) {
            $user->forceFill([
                'login_security_challenge' => null,
                'login_security_key_hash' => null,
                'login_security_key_expires_at' => null,
                'login_security_key_sent_at' => null,
            ])->save();

            return response()->json([
                'status' => 'error',
                'message' => 'Não foi possível enviar a chave de segurança por e-mail. Tente novamente em alguns instantes.',
            ], 502);
        }

        return response()->json([
            'status' => 'two_factor_required',
            'message' => 'Enviamos uma chave de segurança para o seu e-mail. Digite-a para concluir o login.',
            'challenge_id' => $challengeId,
            'expires_in' => 600,
            'remember_me' => $rememberMe,
        ]);
    }

    /**
     * Handle the second step of login using the security key sent by email.
     */
    public function verifyLogin(LoginVerifyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $challengeId = (string) $data['challenge_id'];
        $securityKey = strtoupper((string) $data['security_key']);
        $securityKey = preg_replace('/[^A-Za-z0-9]/', '', $securityKey) ?? '';

        /** @var User|null $user */
        $user = User::query()
            ->where('login_security_challenge', $challengeId)
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não foi possível validar sua chave de segurança. Faça login novamente para receber uma nova chave.',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sua conta está inativa. Entre em contato com o suporte.',
            ], 403);
        }

        $expiresAt = $user->login_security_key_expires_at;
        if (! $expiresAt || $expiresAt->isPast()) {
            $user->forceFill([
                'login_security_challenge' => null,
                'login_security_key_hash' => null,
                'login_security_key_expires_at' => null,
                'login_security_key_sent_at' => null,
            ])->save();

            return response()->json([
                'status' => 'error',
                'message' => 'Sua chave de segurança expirou. Faça login novamente para receber uma nova chave.',
            ], 422);
        }

        $expectedHash = (string) ($user->login_security_key_hash ?? '');
        $providedHash = hash('sha256', $securityKey);

        if ($expectedHash === '' || ! hash_equals($expectedHash, $providedHash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'A chave de acesso está incorreta. Verifique e tente novamente.',
                'errors' => [
                    'security_key' => ['A chave de acesso está incorreta.'],
                ],
            ], 422);
        }

        $user->forceFill([
            'login_security_challenge' => null,
            'login_security_key_hash' => null,
            'login_security_key_expires_at' => null,
            'login_security_key_sent_at' => null,
            'last_login_at' => now(),
        ])->save();

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
