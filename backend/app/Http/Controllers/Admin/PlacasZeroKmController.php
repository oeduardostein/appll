<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPlacasZeroKmQueueJob;
use App\Models\PlacasZeroKmBatch;
use App\Models\PlacasZeroKmRequest;
use App\Models\PlacasZeroKmRunnerState;
use App\Support\PlacaZeroKmParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PlacasZeroKmController extends Controller
{
    private const RUNNER_STALE_MINUTES = 10;

    public function index(): View
    {
        return view('admin.placas-0km.index');
    }

    public function queue(): View
    {
        return view('admin.placas-0km.queue');
    }

    public function queueBatches(): JsonResponse
    {
        $batches = PlacasZeroKmBatch::query()
            ->orderByDesc('id')
            ->limit(30)
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

    public function queueShow(int $batchId): JsonResponse
    {
        $batch = PlacasZeroKmBatch::query()->find($batchId);
        if (!$batch) {
            return response()->json([
                'success' => false,
                'error' => 'Batch não encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $runner = PlacasZeroKmRunnerState::query()->find(1);
        $staleCutoff = now()->subMinutes(self::RUNNER_STALE_MINUTES);
        $runnerIsStuck = $this->isRunnerStuck($runner, $staleCutoff);
        $current = null;
        if ($runner && $runner->current_request_id) {
            $current = PlacasZeroKmRequest::query()->find((int) $runner->current_request_id);
        }

        $requests = PlacasZeroKmRequest::query()
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->limit(500)
            ->get();

        $requests->transform(function (PlacasZeroKmRequest $requestItem) use ($runner, $runnerIsStuck, $staleCutoff): PlacasZeroKmRequest {
            $isStuck = $this->isRequestStuck($requestItem, $runner, $runnerIsStuck, $staleCutoff);
            $displayStatus = $isStuck
                ? 'stuck'
                : match ((string) $requestItem->status) {
                    'succeeded' => 'completed',
                    default => (string) $requestItem->status,
                };

            $requestItem->setAttribute('is_stuck', $isStuck);
            $requestItem->setAttribute('display_status', $displayStatus);

            return $requestItem;
        });

        $statusCounts = PlacasZeroKmRequest::query()
            ->where('batch_id', $batchId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stuckCount = PlacasZeroKmRequest::query()
            ->where('batch_id', $batchId)
            ->where('status', 'running')
            ->where(function ($query) use ($staleCutoff): void {
                $query
                    ->whereNull('started_at')
                    ->orWhere('started_at', '<=', $staleCutoff);
            })
            ->count();

        if ($runnerIsStuck && $runner && $runner->current_request_id) {
            $currentRunningRequest = PlacasZeroKmRequest::query()
                ->where('id', (int) $runner->current_request_id)
                ->where('batch_id', $batchId)
                ->where('status', 'running')
                ->first();

            if ($currentRunningRequest && $currentRunningRequest->started_at && $currentRunningRequest->started_at->gt($staleCutoff)) {
                $stuckCount += 1;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'batch' => $batch,
                'runner' => $runner,
                'runner_status' => $this->resolveRunnerStatus($runner, $runnerIsStuck),
                'summary' => [
                    'stale_minutes' => self::RUNNER_STALE_MINUTES,
                    'total' => (int) ($batch->total ?? 0),
                    'running' => (int) ($statusCounts->get('running', 0)),
                    'stuck' => (int) $stuckCount,
                    'pending' => (int) ($statusCounts->get('pending', 0)),
                    'completed' => (int) ($statusCounts->get('succeeded', 0)),
                    'failed' => (int) ($statusCounts->get('failed', 0)),
                ],
                'current' => $current,
                'requests' => $requests,
            ],
        ]);
    }

    private function resolveRunnerStatus(?PlacasZeroKmRunnerState $runner, bool $runnerIsStuck): string
    {
        if (!$runner || (int) $runner->is_running !== 1) {
            return 'idle';
        }

        return $runnerIsStuck ? 'stuck' : 'running';
    }

    private function isRunnerStuck(?PlacasZeroKmRunnerState $runner, $staleCutoff): bool
    {
        if (!$runner || (int) $runner->is_running !== 1) {
            return false;
        }

        if (!$runner->last_heartbeat_at) {
            return true;
        }

        return $runner->last_heartbeat_at->lte($staleCutoff);
    }

    private function isRequestStuck(
        PlacasZeroKmRequest $requestItem,
        ?PlacasZeroKmRunnerState $runner,
        bool $runnerIsStuck,
        $staleCutoff
    ): bool {
        if ((string) $requestItem->status !== 'running') {
            return false;
        }

        if (!$requestItem->started_at || $requestItem->started_at->lte($staleCutoff)) {
            return true;
        }

        if (!$runnerIsStuck || !$runner) {
            return false;
        }

        return (int) $runner->current_request_id === (int) $requestItem->id;
    }

    public function enqueue(Request $request): JsonResponse
    {
        $cpfCgc = preg_replace('/\D/', '', (string) $request->input('cpf_cgc', ''));
        $chassi = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->input('chassi', '')));
        $numeros = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $request->input('numeros', '')));

        if ($cpfCgc === '' || !in_array(strlen($cpfCgc), [11, 14], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Informe um CPF/CNPJ válido.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($chassi === '' || strlen($chassi) < 17) {
            return response()->json([
                'success' => false,
                'error' => 'Informe um chassi válido.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($numeros !== '' && strlen($numeros) > 4) {
            return response()->json([
                'success' => false,
                'error' => 'O complemento deve ter até 4 caracteres.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = DB::transaction(function () use ($cpfCgc, $chassi, $numeros, $request): array {
            $batch = PlacasZeroKmBatch::query()->create([
                'status' => 'pending',
                'total' => 1,
                'processed' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'source' => 'admin_web',
                'request_ip' => $request->ip(),
            ]);

            $item = PlacasZeroKmRequest::query()->create([
                'batch_id' => $batch->id,
                'cpf_cgc' => $cpfCgc,
                'nome' => null,
                'chassi' => $chassi,
                'numeros' => $numeros !== '' ? $numeros : null,
                'status' => 'pending',
                'attempts' => 0,
            ]);

            return [
                'batch_id' => $batch->id,
                'request_id' => $item->id,
            ];
        });

        if (!config('services.placas0km.client_worker')) {
            ProcessPlacasZeroKmQueueJob::dispatch();
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ], Response::HTTP_CREATED);
    }

    public function consultar(Request $request): JsonResponse
    {
        $cpfCgc = preg_replace('/\D/', '', (string) $request->input('cpf_cgc', ''));
        $chassi = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->input('chassi', '')));
        $numeros = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $request->input('numeros', '')));
        $numeroTentativa = (string) ((int) $request->input('numero_tentativa', 3));
        $tipoRestricao = '-1';
        $placaEscolhaAnterior = '';
        $debug = filter_var($request->input('debug', false), FILTER_VALIDATE_BOOLEAN);

        if ($cpfCgc === '' || !in_array(strlen($cpfCgc), [11, 14], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Informe um CPF/CNPJ válido.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($chassi === '' || strlen($chassi) < 17) {
            return response()->json([
                'success' => false,
                'error' => 'Informe um chassi válido.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($numeros !== '' && strlen($numeros) > 4) {
            return response()->json([
                'success' => false,
                'error' => 'O complemento deve ter até 4 caracteres.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Token de sessão não configurado.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/escolhaPlaca.do',
            'Sec-Fetch-Dest' => 'frame',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Chromium";v="144", "Not:A-Brand";v="8", "Google Chrome";v="144"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
        ];

        $prefixes = [
            'QSY',
            'UEH',
            'UET',
            'UGP',
            'TJX',
            'UEJ',
            'UEV',
            'UGT',
            'UDJ',
            'UEJ',
            'UFB',
            'UGU',
            'UDR',
            'UEP',
            'UGB',
            'UDX',
            'UET',
            'UGM',
        ];

        $placas = [];
        $details = [];

        foreach ($prefixes as $prefix) {
            $payload = [
                'method' => 'pesquisarPlaca',
                'cpfCgcProprietario' => $cpfCgc,
                'nome' => '',
                'tipoRestricaoFinanceira' => $tipoRestricao,
                'chassi' => $chassi,
                'cpfCgcProprietarioFormatado' => $this->formatCpfCnpj($cpfCgc),
                'placaEscolhaAnterior' => $placaEscolhaAnterior,
                'numeroTentativa' => $numeroTentativa,
                'letras' => $prefix,
                'numeros' => $numeros,
            ];

            $result = $this->queryPlacasZeroKm($payload, $headers, $token, $cpfCgc, $chassi);
            if (!$result['success']) {
                if ($debug) {
                    $details[] = [
                        'letras' => $prefix,
                        'error' => $result['error'],
                    ];
                }
                continue;
            }

            $parsed = $result['data'];
            if (!empty($parsed['errors'])) {
                if ($debug) {
                    $details[] = [
                        'letras' => $prefix,
                        'error' => $parsed['errors'][0],
                    ];
                }
                continue;
            }

            $availablePlates = $parsed['placas_disponiveis'] ?? [];
            $lastPlate = !empty($availablePlates) ? end($availablePlates) : null;
            if ($lastPlate) {
                $placas[] = $lastPlate;
                if ($debug) {
                    $details[] = [
                        'letras' => $prefix,
                        'placa' => $lastPlate,
                    ];
                }
            } elseif ($debug) {
                $details[] = [
                    'letras' => $prefix,
                    'error' => 'Nenhuma placa retornada.',
                ];
            }
        }

        $payload = [
            'success' => true,
            'data' => [
                'placas' => $placas,
            ],
        ];

        if ($debug) {
            $payload['data']['detalhes'] = $details;
        }

        return response()->json($payload);
    }

    private function formatCpfCnpj(string $digits): string
    {
        if (strlen($digits) === 11) {
            return sprintf(
                '%s.%s.%s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 3),
                substr($digits, 9, 2)
            );
        }

        if (strlen($digits) === 14) {
            return sprintf(
                '%s.%s.%s/%s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 3),
                substr($digits, 5, 3),
                substr($digits, 8, 4),
                substr($digits, 12, 2)
            );
        }

        return $digits;
    }

    private function queryPlacasZeroKm(
        array $payload,
        array $headers,
        string $token,
        string $cpfCgc,
        string $chassi
    ): array {
        try {
            $cookieTimestamp = now()->setTimezone('America/Sao_Paulo')->format('D M d Y H:i:s');
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->withOptions(['verify' => false])
                ->withCookies([
                    'dataUsuarPublic' => $cookieTimestamp . ' GMT-0300 (Horário Padrão de Brasília)',
                    'naoExibirPublic' => 'sim',
                    'JSESSIONID' => $token,
                ], 'www.e-crvsp.sp.gov.br')
                ->asForm()
                ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/escolhaPlaca.do', $payload);

            if (!$response->successful()) {
                Log::warning('Placas 0KM: Falha na requisição externa', [
                    'status' => $response->status(),
                    'cpf_cgc' => $cpfCgc,
                    'chassi' => $chassi,
                    'response_body' => substr($response->body(), 0, 500),
                ]);

                return [
                    'success' => false,
                    'error' => 'Falha ao consultar o serviço do DETRAN.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Placas 0KM: Erro de conexão', [
                'message' => $e->getMessage(),
                'cpf_cgc' => $cpfCgc,
                'chassi' => $chassi,
            ]);

            return [
                'success' => false,
                'error' => 'Erro ao conectar ao serviço do DETRAN. Tente novamente mais tarde.',
            ];
        }

        return [
            'success' => true,
            'data' => PlacaZeroKmParser::parse($response->body()),
        ];
    }
}
