<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessPlacasZeroKmQueueJob;
use App\Models\PlacasZeroKmBatch;
use App\Models\PlacasZeroKmRequest;
use App\Models\PlacasZeroKmRunnerState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PublicPlacasZeroKmQueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $auth = $this->authorizePublicApi($request);
        if ($auth) {
            return $auth;
        }

        $batches = PlacasZeroKmBatch::query()
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $runner = PlacasZeroKmRunnerState::query()->find(1);

        return response()->json([
            'success' => true,
            'data' => [
                'runner' => $runner,
                'batches' => $batches,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $auth = $this->authorizePublicApi($request);
        if ($auth) {
            return $auth;
        }

        $payload = $request->all();
        $items = $payload['items'] ?? null;

        if (!is_array($items) || empty($items)) {
            return response()->json([
                'success' => false,
                'error' => 'Envie um JSON com a chave items (array).',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $normalized = [];
        foreach ($items as $idx => $item) {
            if (!is_array($item)) {
                return response()->json([
                    'success' => false,
                    'error' => "Item inválido na posição {$idx}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $cpfCgc = preg_replace('/\D/', '', (string) ($item['cpf_cgc'] ?? $item['cpf'] ?? '')) ?? '';
            $nome = trim((string) ($item['nome'] ?? ''));
            $chassi = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($item['chassi'] ?? '')) ?? '');
            $numeros = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($item['numeros'] ?? '')) ?? '');

            if ($cpfCgc === '' || !in_array(strlen($cpfCgc), [11, 14], true)) {
                return response()->json([
                    'success' => false,
                    'error' => "CPF/CNPJ inválido no item {$idx}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($chassi === '' || strlen($chassi) < 17) {
                return response()->json([
                    'success' => false,
                    'error' => "Chassi inválido no item {$idx}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($numeros !== '' && strlen($numeros) > 4) {
                return response()->json([
                    'success' => false,
                    'error' => "Complemento (numeros) inválido no item {$idx}.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $normalized[] = [
                'cpf_cgc' => $cpfCgc,
                'nome' => $nome !== '' ? $nome : null,
                'chassi' => $chassi,
                'numeros' => $numeros !== '' ? $numeros : null,
            ];
        }

        $batch = DB::transaction(function () use ($normalized, $request): PlacasZeroKmBatch {
            $batch = PlacasZeroKmBatch::query()->create([
                'status' => 'pending',
                'total' => count($normalized),
                'processed' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'source' => 'public_web',
                'request_ip' => $request->ip(),
            ]);

            foreach ($normalized as $item) {
                PlacasZeroKmRequest::query()->create([
                    'batch_id' => $batch->id,
                    'cpf_cgc' => $item['cpf_cgc'],
                    'nome' => $item['nome'],
                    'chassi' => $item['chassi'],
                    'numeros' => $item['numeros'],
                    'status' => 'pending',
                    'attempts' => 0,
                ]);
            }

            return $batch;
        });

        ProcessPlacasZeroKmQueueJob::dispatch();

        return response()->json([
            'success' => true,
            'data' => [
                'batch_id' => $batch->id,
                'total' => $batch->total,
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $batchId): JsonResponse
    {
        $auth = $this->authorizePublicApi($request);
        if ($auth) {
            return $auth;
        }

        $batch = PlacasZeroKmBatch::query()->find($batchId);
        if (!$batch) {
            return response()->json([
                'success' => false,
                'error' => 'Batch não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $runner = PlacasZeroKmRunnerState::query()->find(1);
        $current = null;
        if ($runner && $runner->current_request_id) {
            $current = PlacasZeroKmRequest::query()->find((int) $runner->current_request_id);
        }

        $requests = PlacasZeroKmRequest::query()
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->limit(500)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'batch' => $batch,
                'runner' => $runner,
                'current' => $current,
                'requests' => $requests,
            ],
        ]);
    }

    private function authorizePublicApi(Request $request): ?JsonResponse
    {
        $apiKey = (string) config('services.public_placas0km.key', '');
        if ($apiKey === '') {
            return null;
        }

        $headerKey = (string) $request->header('X-Public-Api-Key', '');
        if (hash_equals($apiKey, $headerKey)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'error' => 'Não autorizado.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}

