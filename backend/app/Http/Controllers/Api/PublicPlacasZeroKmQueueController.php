<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessPlacasZeroKmQueueJob;
use App\Jobs\ProcessPlacasZeroKmOcrJob;
use App\Models\PlacasZeroKmBatch;
use App\Models\PlacasZeroKmRequest;
use App\Models\PlacasZeroKmRunnerState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        if (!config('services.placas0km.client_worker')) {
            ProcessPlacasZeroKmQueueJob::dispatch();
        }

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

    public function uploadScreenshot(Request $request): JsonResponse
    {
        $auth = $this->authorizePublicApi($request);
        if ($auth) {
            return $auth;
        }

        $requestId = (int) $request->input('request_id', 0);
        if ($requestId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'request_id inválido.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'error' => 'Arquivo inválido.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $req = PlacasZeroKmRequest::query()->find($requestId);
        if (!$req) {
            return response()->json([
                'success' => false,
                'error' => 'Request não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $disk = 'public';
        $path = $file->storeAs(
            'placas-0km/' . $requestId,
            (string) Str::uuid() . '.png',
            $disk
        );

        $url = Storage::disk($disk)->url($path);

        $payload = $req->response_payload ?? [];
        if (!is_array($payload)) {
            $payload = [];
        }
        $data = $payload['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }
        $data['screenshot_path'] = $path;
        $data['screenshot_url'] = $url;
        $payload['data'] = $data;
        $req->response_payload = $payload;
        $req->save();

        ProcessPlacasZeroKmOcrJob::dispatch($requestId, $disk, $path);

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => $requestId,
                'screenshot_path' => $path,
                'screenshot_url' => $url,
            ],
        ]);
    }

    public function resetRunnerState(Request $request): JsonResponse
    {
        $auth = $this->authorizePublicApi($request);
        if ($auth) {
            return $auth;
        }

        $runnerId = (int) $request->input('runner_id', 1);
        $requestId = (int) $request->input('request_id', 10);

        if ($runnerId <= 0 || $requestId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'runner_id e request_id devem ser maiores que zero.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = DB::transaction(function () use ($runnerId, $requestId): array {
            $runnerUpdated = DB::table('placas_zero_km_runner_state')
                ->where('id', $runnerId)
                ->update([
                    'is_running' => 0,
                    'current_request_id' => null,
                    'last_heartbeat_at' => now(),
                    'updated_at' => now(),
                ]);

            $requestUpdated = DB::table('placas_zero_km_requests')
                ->where('id', $requestId)
                ->where('status', 'running')
                ->update([
                    'status' => 'pending',
                    'response_error' => null,
                    'started_at' => null,
                    'finished_at' => null,
                    'updated_at' => now(),
                ]);

            return [
                'runner_updated_rows' => $runnerUpdated,
                'request_updated_rows' => $requestUpdated,
                'runner_id' => $runnerId,
                'request_id' => $requestId,
            ];
        });

        if (!config('services.placas0km.client_worker')) {
            ProcessPlacasZeroKmQueueJob::dispatch();
        }

        return response()->json([
            'success' => true,
            'data' => $result,
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
