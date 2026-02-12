<?php

namespace App\Jobs;

use App\Models\PlacasZeroKmBatch;
use App\Models\PlacasZeroKmRequest;
use App\Models\PlacasZeroKmRunnerState;
use App\Services\PlacasZeroKmConsultaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPlacasZeroKmQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 300;

    public $tries = 1;

    public function handle(PlacasZeroKmConsultaService $consultaService): void
    {
        $picked = DB::transaction(function (): ?PlacasZeroKmRequest {
            /** @var PlacasZeroKmRunnerState|null $state */
            $state = PlacasZeroKmRunnerState::query()->where('id', 1)->lockForUpdate()->first();
            if (!$state) {
                return null;
            }

            if ((int) $state->is_running === 1) {
                $previousHeartbeat = $state->last_heartbeat_at;
                $isStale = $previousHeartbeat && $previousHeartbeat->lt(now()->subMinutes(10));
                if (!$isStale) {
                    $state->last_heartbeat_at = now();
                    $state->save();
                    return null;
                }

                Log::warning('Placas 0KM Queue: runner state estava travado (stale), liberando.', [
                    'current_request_id' => $state->current_request_id,
                    'last_heartbeat_at' => $previousHeartbeat,
                ]);
                $state->is_running = 0;
                $state->current_request_id = null;
                $state->save();
            }

            $state->last_heartbeat_at = now();
            $state->save();

            /** @var PlacasZeroKmRequest|null $next */
            $next = PlacasZeroKmRequest::query()
                ->where('status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$next) {
                return null;
            }

            $next->status = 'running';
            $next->attempts = ((int) $next->attempts) + 1;
            $next->started_at = now();
            $next->save();

            $state->is_running = 1;
            $state->current_request_id = $next->id;
            $state->last_heartbeat_at = now();
            $state->save();

            PlacasZeroKmBatch::query()
                ->where('id', $next->batch_id)
                ->update(['status' => 'running', 'updated_at' => now()]);

            return $next;
        });

        if (!$picked) {
            return;
        }

        $requestId = $picked->id;
        $batchId = $picked->batch_id;

        try {
            $result = $consultaService->consultar([
                'cpf_cgc' => $picked->cpf_cgc,
                'nome' => $picked->nome,
                'chassi' => $picked->chassi,
                'numeros' => $picked->numeros,
            ]);

            $picked->response_payload = $result;
            $picked->response_error = ($result['success'] ?? false) ? null : (string) ($result['error'] ?? 'Falha na consulta.');
            $picked->status = ($result['success'] ?? false) ? 'succeeded' : 'failed';
            $picked->finished_at = now();
            $picked->save();
        } catch (\Throwable $e) {
            Log::error('Placas 0KM Queue: erro ao processar request', [
                'request_id' => $requestId,
                'batch_id' => $batchId,
                'message' => $e->getMessage(),
            ]);

            $picked->response_payload = null;
            $picked->response_error = $e->getMessage();
            $picked->status = 'failed';
            $picked->finished_at = now();
            $picked->save();
        } finally {
            DB::transaction(function () use ($requestId): void {
                /** @var PlacasZeroKmRunnerState|null $state */
                $state = PlacasZeroKmRunnerState::query()->where('id', 1)->lockForUpdate()->first();
                if (!$state) {
                    return;
                }

                if ((int) $state->current_request_id === (int) $requestId) {
                    $state->current_request_id = null;
                    $state->is_running = 0;
                    $state->last_heartbeat_at = now();
                    $state->save();
                }
            });
        }

        $this->refreshBatchCounters($batchId);

        if (PlacasZeroKmRequest::query()->where('status', 'pending')->exists()) {
            static::dispatch()->delay(now()->addSeconds(1));
        }
    }

    private function refreshBatchCounters(int $batchId): void
    {
        $batch = PlacasZeroKmBatch::query()->find($batchId);
        if (!$batch) {
            return;
        }

        $total = PlacasZeroKmRequest::query()->where('batch_id', $batchId)->count();
        $succeeded = PlacasZeroKmRequest::query()->where('batch_id', $batchId)->where('status', 'succeeded')->count();
        $failed = PlacasZeroKmRequest::query()->where('batch_id', $batchId)->where('status', 'failed')->count();
        $running = PlacasZeroKmRequest::query()->where('batch_id', $batchId)->where('status', 'running')->count();
        $pending = PlacasZeroKmRequest::query()->where('batch_id', $batchId)->where('status', 'pending')->count();

        $processed = $succeeded + $failed;

        $status = $batch->status;
        if ($pending === 0 && $running === 0) {
            $status = 'completed';
        } elseif ($running > 0) {
            $status = 'running';
        }

        $batch->fill([
            'status' => $status,
            'total' => $total,
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);
        $batch->save();
    }
}
