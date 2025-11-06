<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CepLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $cepRaw = (string) $request->query('cep', '');
        $cep = preg_replace('/\D/', '', $cepRaw);

        if (strlen($cep) !== 8) {
            return response()->json([
                'message' => 'Informe um CEP válido com 8 dígitos.',
            ], 422);
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível consultar o endereço agora. Tente novamente em instantes.',
            ], 503);
        }

        if (! $response->ok()) {
            return response()->json([
                'message' => 'Serviço de CEP temporariamente indisponível.',
            ], 503);
        }

        $data = $response->json();

        if (! is_array($data) || ($data['erro'] ?? false)) {
            return response()->json([
                'message' => 'CEP não encontrado.',
            ], 404);
        }

        return response()->json([
            'cep' => $data['cep'] ?? null,
            'logradouro' => $data['logradouro'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['localidade'] ?? null,
            'uf' => $data['uf'] ?? null,
        ]);
    }
}
