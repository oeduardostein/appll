<?php

namespace App\Http\Controllers\Api;

use App\Services\PlacasZeroKmConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class PlacasZeroKmConsultaController extends Controller
{
    public function __construct(private readonly PlacasZeroKmConsultaService $consultaService)
    {
    }

    /**
     * Consulta placas disponíveis (0km) no portal e-CRVSP.
     *
     * Espera JSON:
     * - cpf_cgc (obrigatório)
     * - chassi (obrigatório)
     * - nome (opcional, ignorado)
     * - numeros (opcional, até 4)
     * - numero_tentativa (opcional, default 3)
     * - tipo_restricao_financeira (opcional, default -1)
     * - placa_escolha_anterior (opcional)
     * - prefixes (opcional: array de 3 letras)
     */
    public function __invoke(Request $request): JsonResponse
    {
        $apiKey = (string) config('services.public_placas0km.key', '');
        if ($apiKey !== '') {
            $headerKey = (string) $request->header('X-Public-Api-Key', '');
            if (!hash_equals($apiKey, $headerKey)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Não autorizado.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        $result = $this->consultaService->consultar($request->all());

        $status = ($result['success'] ?? false) ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY;
        if (($result['success'] ?? false) === false && (($result['error'] ?? '') === 'Token de sessão não configurado.')) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return response()->json($result, $status);
    }
}
