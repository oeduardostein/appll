<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesquisa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PesquisaController extends Controller
{
    /**
     * Retorna as últimas 5 pesquisas do usuário autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->findUserFromRequest($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $items = $user->pesquisas()
            ->latest()
            ->take(5)
            ->get([
                'id',
                'nome',
                'placa',
                'renavam',
                'chassi',
                'opcao_pesquisa',
                'created_at',
            ]);

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Retorna todas as pesquisas do usuário feitas nos últimos 30 dias.
     */
    public function lastMonth(Request $request): JsonResponse
    {
        $user = $this->findUserFromRequest($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $startDate = Carbon::now()->subMonth();

        $items = $user->pesquisas()
            ->where('created_at', '>=', $startDate)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'nome',
                'placa',
                'renavam',
                'chassi',
                'opcao_pesquisa',
                'created_at',
            ]);

        return response()->json([
            'data' => $items,
            'period_start' => $startDate->toIso8601String(),
            'period_end' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Armazena nova pesquisa genérica do usuário.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->findUserFromRequest($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'placa' => ['nullable', 'string', 'max:10'],
            'renavam' => ['nullable', 'string', 'max:20'],
            'chassi' => ['nullable', 'string', 'max:32'],
            'opcao_pesquisa' => ['nullable', 'string', 'max:10'],
        ]);

        $pesquisa = Pesquisa::create([
            'user_id' => $user->id,
            ...$data,
        ]);

        return response()->json([
            'message' => 'Pesquisa registrada com sucesso.',
            'data' => $pesquisa,
        ], 201);
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
