<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\FindsUserFromApiToken;
use App\Http\Controllers\Controller;
use App\Models\Pesquisa;
use App\Models\User;
use App\Support\CreditValueResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PesquisaController extends Controller
{
    use FindsUserFromApiToken;

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

        $resolver = CreditValueResolver::forUser($user);

        $pesquisa = Pesquisa::create([
            'user_id' => $user->id,
            'credit_value' => $resolver->resolveForPesquisa($data['nome']),
            ...$data,
        ]);

        return response()->json([
            'message' => 'Pesquisa registrada com sucesso.',
            'data' => $pesquisa,
        ], 201);
    }


    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Não autenticado.',
        ], 401);
    }
}
