<?php

namespace App\Jobs;

use App\Models\PlacasZeroKmRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessPlacasZeroKmOcrJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 120;

    public $tries = 2;

    public function __construct(
        public int $requestId,
        public string $disk,
        public string $path,
    ) {}

    public function handle(): void
    {
        if (!config('services.placas0km_ocr.enabled')) {
            return;
        }

        $req = PlacasZeroKmRequest::query()->find($this->requestId);
        if (!$req) {
            return;
        }

        if (!Storage::disk($this->disk)->exists($this->path)) {
            Log::warning('OCR: arquivo não encontrado', [
                'request_id' => $this->requestId,
                'path' => $this->path,
            ]);
            return;
        }

        $fullPath = Storage::disk($this->disk)->path($this->path);
        $lang = (string) config('services.placas0km_ocr.lang', 'por');
        $bin = (string) config('services.placas0km_ocr.tesseract', 'tesseract');

        $text = $this->runTesseract($bin, $fullPath, $lang);
        if ($text === null) {
            Log::warning('OCR: falha ao executar tesseract', [
                'request_id' => $this->requestId,
            ]);
            return;
        }

        $normalized = $this->normalizeText($text);
        $plates = $this->extractPlates($normalized);
        $errorMessage = $this->detectError($normalized);

        $payload = $req->response_payload ?? [];
        if (!is_array($payload)) {
            $payload = [];
        }
        $data = $payload['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }
        $data['ocr'] = [
            'text' => $text,
            'normalized_text' => $normalized,
            'plates' => $plates,
            'error_message' => $errorMessage,
        ];
        $payload['data'] = $data;
        $req->response_payload = $payload;

        if ($errorMessage) {
            $req->status = 'failed';
            $req->response_error = $errorMessage;
        } elseif (!empty($plates)) {
            $req->status = 'succeeded';
            $req->response_error = null;
        }

        $req->save();
    }

    private function runTesseract(string $bin, string $path, string $lang): ?string
    {
        $cmd = escapeshellcmd($bin) . ' ' . escapeshellarg($path) . ' stdout -l ' . escapeshellarg($lang) . ' 2>&1';
        $output = [];
        $exitCode = 1;
        @exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        return trim(implode("\n", $output));
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtoupper($text, 'UTF-8');
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }

    /**
     * @return string[]
     */
    private function extractPlates(string $text): array
    {
        $plates = [];

        $patterns = [
            '/[A-Z]{3}-\\d{4}/',       // ABC-1234
            '/[A-Z]{3}-\\d[A-Z]\\d{2}/', // UFY-9A48
            '/[A-Z]{3}\\d[A-Z]\\d{2}/',  // UFY9A48
        ];

        foreach ($patterns as $rx) {
            if (preg_match_all($rx, $text, $m)) {
                foreach ($m[0] as $p) {
                    $plates[] = $p;
                }
            }
        }

        return array_values(array_unique($plates));
    }

    private function detectError(string $text): ?string
    {
        $known = [
            'FICHA CADASTRAL JA EXISTENTE',
            'FICHA CADASTRAL JA EXISTE',
            'FICHA JA EXISTENTE',
            'FICHA JA EXISTE',
            'ERRO',
            'NAO EXISTE',
            'INDEFERIDO',
            'NEGADO',
        ];

        foreach ($known as $k) {
            if (strpos($text, $k) !== false) {
                return $k;
            }
        }

        if (
            preg_match('/\b[A-Z0-9]{3,6}-\d{2,4}\b/', $text, $codeMatch) &&
            (
                strpos($text, 'FICHA') !== false ||
                strpos($text, 'CADASTRAL') !== false ||
                strpos($text, 'EXIST') !== false ||
                strpos($text, 'NEGAD') !== false ||
                strpos($text, 'INDEFERID') !== false ||
                strpos($text, 'ERRO') !== false ||
                strpos($text, 'CANCELAR') !== false
            )
        ) {
            return 'ERRO DE MODAL (' . $codeMatch[0] . ')';
        }

        return null;
    }
}
