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
        $maxPlates = max(1, (int) config('services.placas0km_ocr.max_plates', 18));

        $textPasses = $this->runTesseractPasses($bin, $fullPath, $lang);
        if (empty($textPasses)) {
            Log::warning('OCR: falha ao executar tesseract', [
                'request_id' => $this->requestId,
            ]);
            return;
        }

        $text = $textPasses[0];
        $combinedText = implode("\n", $textPasses);
        $combinedNormalized = $this->normalizeText($combinedText);
        $plates = $this->aggregatePlatesFromPasses($textPasses, $maxPlates);
        $errorMessage = $this->detectError($combinedNormalized);

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
            'normalized_text' => $combinedNormalized,
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

    /**
     * @return string[]
     */
    private function runTesseractPasses(string $bin, string $path, string $lang): array
    {
        $passes = [
            [
                '--psm', '6',
                '-c', 'tessedit_char_whitelist=ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-',
                '-c', 'preserve_interword_spaces=1',
            ],
            [
                '--psm', '11',
                '-c', 'tessedit_char_whitelist=ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-',
                '-c', 'preserve_interword_spaces=1',
            ],
            [],
        ];

        $texts = [];
        foreach ($passes as $extraArgs) {
            $text = $this->runSingleTesseractPass($bin, $path, $lang, $extraArgs);
            if ($text === null) {
                continue;
            }
            if ($text === '') {
                continue;
            }
            $texts[] = $text;
        }

        return array_values(array_unique($texts));
    }

    /**
     * @param string[] $extraArgs
     */
    private function runSingleTesseractPass(string $bin, string $path, string $lang, array $extraArgs = []): ?string
    {
        $baseParts = [
            escapeshellcmd($bin),
            escapeshellarg($path),
            'stdout',
            '-l',
            escapeshellarg($lang),
        ];
        foreach ($extraArgs as $arg) {
            $baseParts[] = escapeshellarg((string) $arg);
        }

        $cmd = implode(' ', $baseParts) . ' 2>&1';
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
        $seen = [];
        $plates = [];

        $addPlate = function (?string $plate) use (&$seen, &$plates): void {
            if (!$plate) {
                return;
            }
            if (isset($seen[$plate])) {
                return;
            }
            $seen[$plate] = true;
            $plates[] = $plate;
        };

        $strictPatterns = [
            '/\b[A-Z]{3}[-\s]?\d{4}\b/u',
            '/\b[A-Z]{3}[-\s]?\d[A-Z]\d{2}\b/u',
        ];

        foreach ($strictPatterns as $pattern) {
            if (!preg_match_all($pattern, $text, $matches)) {
                continue;
            }
            foreach (($matches[0] ?? []) as $rawCandidate) {
                $addPlate($this->normalizePlateCandidate((string) $rawCandidate));
            }
        }

        if (preg_match_all('/\b[A-Z0-9]{3}[-\s]?[A-Z0-9]{4}\b/u', $text, $matches)) {
            foreach (($matches[0] ?? []) as $rawCandidate) {
                $rawCandidate = (string) $rawCandidate;
                $compact = preg_replace('/[^A-Z0-9]/', '', strtoupper($rawCandidate)) ?? '';
                if ($compact === '') {
                    continue;
                }
                if (strlen($compact) !== 7) {
                    continue;
                }
                preg_match_all('/\d/', $compact, $digitMatches);
                $digitCount = count($digitMatches[0] ?? []);
                $hasSeparator = preg_match('/[-\s]/', $rawCandidate) === 1;
                if (!$hasSeparator) {
                    if (!preg_match('/^[A-Z]/', $compact)) {
                        continue;
                    }
                    $first3 = substr($compact, 0, 3);
                    preg_match_all('/[A-Z]/', $first3, $first3Letters);
                    if (count($first3Letters[0] ?? []) < 2) {
                        continue;
                    }
                }
                if (!$hasSeparator && $digitCount < 2) {
                    continue;
                }
                if ($hasSeparator && $digitCount < 1) {
                    continue;
                }
                $addPlate($this->normalizePlateCandidate($rawCandidate));
            }
        }

        if (preg_match_all('/[A-Z0-9]{3}-[A-Z0-9]{4}/u', $text, $matches)) {
            foreach (($matches[0] ?? []) as $chunk) {
                $addPlate($this->normalizePlateCandidate((string) $chunk));
            }
        }

        return $plates;
    }

    private function normalizePlateCandidate(string $rawCandidate): ?string
    {
        $compact = preg_replace('/[^A-Z0-9]/', '', strtoupper($rawCandidate)) ?? '';
        if (strlen($compact) !== 7) {
            return null;
        }

        if (preg_match('/^[A-Z]{3}\d[A-Z]\d{2}$/', $compact)) {
            return $this->formatPlate($compact);
        }

        if (preg_match('/^[A-Z]{3}\d{4}$/', $compact)) {
            return $this->formatPlate($compact);
        }

        $mercosul = $this->normalizeMercosulPlate($compact);
        if ($mercosul !== null) {
            return $this->formatPlate($mercosul);
        }

        $classic = $this->normalizeClassicPlate($compact);
        if ($classic !== null) {
            return $this->formatPlate($classic);
        }

        return null;
    }

    private function normalizeClassicPlate(string $compact): ?string
    {
        if (strlen($compact) !== 7) {
            return null;
        }

        $out = '';
        for ($i = 0; $i < 3; $i++) {
            $letter = $this->coerceLetter($compact[$i]);
            if ($letter === null) {
                return null;
            }
            $out .= $letter;
        }

        for ($i = 3; $i < 7; $i++) {
            $digit = $this->coerceDigit($compact[$i]);
            if ($digit === null) {
                return null;
            }
            $out .= $digit;
        }

        if (!preg_match('/^[A-Z]{3}\d{4}$/', $out)) {
            return null;
        }

        return $out;
    }

    private function normalizeMercosulPlate(string $compact): ?string
    {
        if (strlen($compact) !== 7) {
            return null;
        }

        $p0 = $this->coerceLetter($compact[0]);
        $p1 = $this->coerceLetter($compact[1]);
        $p2 = $this->coerceLetter($compact[2]);
        $p3 = $this->coerceDigit($compact[3]);
        $p4 = $this->coerceLetter($compact[4]);
        $p5 = $this->coerceDigit($compact[5]);
        $p6 = $this->coerceDigit($compact[6]);

        if ($p0 === null || $p1 === null || $p2 === null || $p3 === null || $p4 === null || $p5 === null || $p6 === null) {
            return null;
        }

        $out = $p0 . $p1 . $p2 . $p3 . $p4 . $p5 . $p6;
        if (!preg_match('/^[A-Z]{3}\d[A-Z]\d{2}$/', $out)) {
            return null;
        }

        return $out;
    }

    private function coerceLetter(string $char): ?string
    {
        $upper = strtoupper($char);
        if ($upper === '') {
            return null;
        }
        if (preg_match('/^[A-Z]$/', $upper)) {
            return $upper;
        }

        return match ($upper) {
            '0' => 'O',
            '1' => 'J',
            '2' => 'Z',
            '4' => 'A',
            '5' => 'S',
            '6' => 'G',
            '7' => 'T',
            '8' => 'B',
            '9' => 'G',
            default => null,
        };
    }

    private function coerceDigit(string $char): ?string
    {
        $upper = strtoupper($char);
        if ($upper === '') {
            return null;
        }
        if (preg_match('/^\d$/', $upper)) {
            return $upper;
        }

        return match ($upper) {
            'A' => '4',
            'O', 'Q', 'D' => '0',
            'I', 'L', 'J', 'T' => '1',
            'Z' => '2',
            'S' => '5',
            'G' => '6',
            'B' => '8',
            default => null,
        };
    }

    private function formatPlate(string $compact): string
    {
        return substr($compact, 0, 3) . '-' . substr($compact, 3);
    }

    /**
     * @param string[] $textPasses
     * @return string[]
     */
    private function aggregatePlatesFromPasses(array $textPasses, int $maxPlates = 18): array
    {
        $weights = [3, 2, 1];
        $stats = [];

        foreach ($textPasses as $idx => $text) {
            $normalized = $this->normalizeText((string) $text);
            if ($normalized === '') {
                continue;
            }

            $passKey = 'p' . $idx;
            $weight = $weights[$idx] ?? 1;
            $plates = $this->extractPlates($normalized);
            foreach ($plates as $plate) {
                if (!isset($stats[$plate])) {
                    $stats[$plate] = [
                        'plate' => $plate,
                        'score' => 0,
                        'hits' => 0,
                        'passes' => [],
                    ];
                }

                $stats[$plate]['score'] += $weight;
                $stats[$plate]['hits'] += 1;
                $stats[$plate]['passes'][$passKey] = true;
            }
        }

        if (empty($stats)) {
            return [];
        }

        $ranked = array_values($stats);
        usort($ranked, static function (array $a, array $b): int {
            $aPassCount = count($a['passes'] ?? []);
            $bPassCount = count($b['passes'] ?? []);
            if ($aPassCount !== $bPassCount) {
                return $bPassCount <=> $aPassCount;
            }

            $aScore = (int) ($a['score'] ?? 0);
            $bScore = (int) ($b['score'] ?? 0);
            if ($aScore !== $bScore) {
                return $bScore <=> $aScore;
            }

            $aHits = (int) ($a['hits'] ?? 0);
            $bHits = (int) ($b['hits'] ?? 0);
            if ($aHits !== $bHits) {
                return $bHits <=> $aHits;
            }

            return strcmp((string) ($a['plate'] ?? ''), (string) ($b['plate'] ?? ''));
        });

        $supported = array_values(array_filter($ranked, static fn(array $row): bool => count($row['passes'] ?? []) >= 2));
        $phases = [$supported, $ranked];
        $selected = [];
        foreach ($phases as $phase) {
            foreach ($phase as $candidate) {
                if (count($selected) >= $maxPlates) {
                    break 2;
                }

                $plate = (string) ($candidate['plate'] ?? '');
                if ($plate === '') {
                    continue;
                }

                $isVariant = false;
                foreach ($selected as $chosen) {
                    if ($this->areLikelyVariantPlates((string) $chosen['plate'], $plate)) {
                        $isVariant = true;
                        break;
                    }
                }
                if ($isVariant) {
                    continue;
                }

                $selected[] = $candidate;
            }
        }

        return array_values(array_map(
            static fn(array $row): string => (string) ($row['plate'] ?? ''),
            $selected
        ));
    }

    private function areLikelyVariantPlates(string $a, string $b): bool
    {
        $compactA = preg_replace('/[^A-Z0-9]/', '', strtoupper($a)) ?? '';
        $compactB = preg_replace('/[^A-Z0-9]/', '', strtoupper($b)) ?? '';
        if (strlen($compactA) !== 7 || strlen($compactB) !== 7) {
            return false;
        }

        $hardDiffs = 0;
        $hasAnyDiff = false;
        for ($i = 0; $i < 7; $i++) {
            $ca = $compactA[$i];
            $cb = $compactB[$i];
            if ($ca === $cb) {
                continue;
            }
            $hasAnyDiff = true;
            if (!$this->isConfusablePair($ca, $cb)) {
                $hardDiffs += 1;
            }
        }

        if (!$hasAnyDiff) {
            return true;
        }

        return $hardDiffs <= 1;
    }

    private function isConfusablePair(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $map = [
            '0' => ['O', 'Q', 'D'],
            'O' => ['0'],
            'Q' => ['0'],
            'D' => ['0'],
            '1' => ['I', 'L', 'J', 'T'],
            'I' => ['1'],
            'L' => ['1'],
            'J' => ['1'],
            'T' => ['1'],
            '2' => ['Z'],
            'Z' => ['2'],
            '4' => ['A'],
            'A' => ['4'],
            '5' => ['S', '8'],
            'S' => ['5'],
            '6' => ['G'],
            'G' => ['6'],
            '8' => ['B', '5', '3'],
            'B' => ['8'],
            '3' => ['8'],
            'U' => ['V'],
            'V' => ['U'],
            'E' => ['F'],
            'F' => ['E'],
        ];

        return in_array($b, $map[$a] ?? [], true);
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
