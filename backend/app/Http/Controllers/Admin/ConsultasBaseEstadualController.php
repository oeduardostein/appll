<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseEstadualController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsultasBaseEstadualController extends Controller
{
    public function index(): View
    {
        return view('admin.consultas-base-estadual.index');
    }

    public function consultar(Request $request): JsonResponse
    {
        $placa = strtoupper(trim((string) $request->input('placa', '')));
        $renavam = trim((string) $request->input('renavam', ''));
        $captchaResponse = trim((string) $request->input('captcha_response', ''));

        if ($placa === '') {
            return response()->json([
                'success' => false,
                'error' => 'Placa não informada.',
            ], 422);
        }

        if ($captchaResponse === '') {
            return response()->json([
                'success' => false,
                'error' => 'Captcha não informado.',
            ], 422);
        }

        $debugMode = filter_var($request->input('debug', false), FILTER_VALIDATE_BOOLEAN);

        try {
            $result = $this->queryBaseEstadual($placa, $renavam, $captchaResponse, $debugMode);

            if (isset($result['error'])) {
                $responsePayload = [
                    'success' => false,
                    'error' => $result['error'],
                ];

                if ($debugMode && isset($result['debug'])) {
                    $responsePayload['meta'] = $result['debug'];
                }

                return response()->json($responsePayload);
            }

            $payload = $result['data'] ?? [];
            $campos = $this->flattenForExport($payload);

            $responsePayload = [
                'success' => true,
                'placa' => $placa,
                'renavam' => $renavam,
                'campos' => $campos,
                'message' => 'Consulta realizada com sucesso.',
            ];

            if ($debugMode && isset($result['debug'])) {
                $responsePayload['meta'] = $result['debug'];
            }

            return response()->json($responsePayload);
        } catch (\Throwable $e) {
            Log::error('ConsultasBaseEstadual: Erro na consulta', [
                'placa' => $placa,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao consultar: ' . $e->getMessage(),
            ]);
        }
    }

    public function exportar(Request $request): StreamedResponse
    {
        $dados = $request->input('dados', []);
        if (!is_array($dados)) {
            $dados = [];
        }

        $inputColumns = [];
        $requestColumns = $request->input('colunas', []);
        if (is_array($requestColumns)) {
            foreach ($requestColumns as $column) {
                if (!is_string($column)) {
                    continue;
                }

                $column = trim($column);
                if ($column === '' || in_array($column, $inputColumns, true)) {
                    continue;
                }

                $inputColumns[] = $column;
            }
        }

        if ($inputColumns === []) {
            $inputColumns = ['PLACA'];
        }

        $baseColumns = [];
        foreach ($dados as $linha) {
            if (!is_array($linha)) {
                continue;
            }

            $campos = $linha['campos'] ?? [];
            if (!is_array($campos)) {
                continue;
            }

            foreach (array_keys($campos) as $column) {
                if (!is_string($column) || $column === '') {
                    continue;
                }

                if (!in_array($column, $baseColumns, true)) {
                    $baseColumns[] = $column;
                }
            }
        }

        $headers = [
            ...$inputColumns,
            'CONSULTA_STATUS',
            'CONSULTA_MENSAGEM',
            ...$baseColumns,
        ];

        $rows = [];
        foreach ($dados as $linha) {
            if (!is_array($linha)) {
                continue;
            }

            $values = $linha['values'] ?? [];
            if (!is_array($values)) {
                $values = [];
            }

            $campos = $linha['campos'] ?? [];
            if (!is_array($campos)) {
                $campos = [];
            }

            $rowValues = [];

            foreach ($inputColumns as $index => $_columnName) {
                $rowValues[] = $this->normalizeExportValue($values[$index] ?? '');
            }

            $rowValues[] = $this->normalizeExportValue($linha['consulta_status'] ?? '');
            $rowValues[] = $this->normalizeExportValue($linha['consulta_mensagem'] ?? '');

            foreach ($baseColumns as $columnName) {
                $rowValues[] = $this->normalizeExportValue($campos[$columnName] ?? '');
            }

            $rows[] = $rowValues;
        }

        $timestamp = date('Y-m-d_His');

        if (!class_exists(Spreadsheet::class) || !class_exists(Xlsx::class)) {
            $filename = 'consultas_base_estadual_' . $timestamp . '.csv';

            return response()->streamDownload(function () use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, $headers, ';');

                foreach ($rows as $row) {
                    fputcsv($handle, $row, ';');
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $filename = 'consultas_base_estadual_' . $timestamp . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Consultas Base Estadual');

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0B4EA2'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
        ];
        $headerEndColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$headerEndColumn}1")->applyFromArray($headerStyle);

        $rowIndex = 2;
        foreach ($rows as $rowValues) {
            foreach ($rowValues as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex, $value);
            }
            $rowIndex++;
        }

        for ($colIndex = 1; $colIndex <= count($headers); $colIndex++) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function queryBaseEstadual(
        string $placa,
        string $renavam,
        string $captchaResponse,
        bool $captureDebug = false
    ): array {
        $payload = [
            'placa' => preg_replace('/[^A-Za-z0-9]/', '', $placa),
            'renavam' => preg_replace('/\D/', '', $renavam),
            'captcha' => strtoupper($captchaResponse),
        ];

        $targetUrl = '/api/base-estadual';
        $apiRequest = Request::create($targetUrl, 'GET', $payload);

        $debugInfo = null;
        if ($captureDebug) {
            $debugInfo = [
                'captcha' => $payload['captcha'],
                'payload' => $payload,
                'url' => url($targetUrl),
                'response' => null,
            ];
        }

        /** @var SymfonyResponse $response */
        $response = app(BaseEstadualController::class)->__invoke($apiRequest);
        $status = $response->getStatusCode();
        $body = $response->getContent();
        $decoded = json_decode((string) $body, true);

        if ($captureDebug && $debugInfo !== null) {
            $debugInfo['response'] = [
                'status' => $status,
                'body' => $this->truncateResponse((string) $body),
            ];
        }

        if ($status !== SymfonyResponse::HTTP_OK) {
            $errorMessage = is_array($decoded) ? ($decoded['message'] ?? null) : null;
            if (!is_string($errorMessage) || trim($errorMessage) === '') {
                $errorMessage = 'Erro ao consultar a base estadual.';
            }

            return [
                'error' => $errorMessage,
                'debug' => $debugInfo,
            ];
        }

        return [
            'data' => is_array($decoded) ? $decoded : [],
            'debug' => $debugInfo,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function flattenForExport(array $payload): array
    {
        $pathValues = [];
        $this->collectPathValues($payload, '', $pathValues);

        $columns = [];
        foreach ($pathValues as $path => $value) {
            $column = $this->pathToColumn((string) $path);
            if (isset($columns[$column])) {
                $suffix = 2;
                while (isset($columns[$column . '_' . $suffix])) {
                    $suffix++;
                }
                $column .= '_' . $suffix;
            }

            $columns[$column] = $this->normalizeExportValue($value);
        }

        return $columns;
    }

    /**
     * @param array<string, mixed> $output
     */
    private function collectPathValues(mixed $value, string $path, array &$output): void
    {
        if (is_array($value)) {
            if ($value === []) {
                return;
            }

            foreach ($value as $key => $nested) {
                $segment = is_int($key) ? (string) ($key + 1) : (string) $key;
                $nextPath = $path === '' ? $segment : ($path . '.' . $segment);
                $this->collectPathValues($nested, $nextPath, $output);
            }

            return;
        }

        if (is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $output[$path] = $encoded !== false ? $encoded : '';
            return;
        }

        $output[$path] = $value;
    }

    private function pathToColumn(string $path): string
    {
        $segments = explode('.', $path);
        $normalized = ['BASE'];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $segment = str_replace(['á', 'à', 'â', 'ã', 'ä'], 'a', $segment);
            $segment = str_replace(['é', 'è', 'ê', 'ë'], 'e', $segment);
            $segment = str_replace(['í', 'ì', 'î', 'ï'], 'i', $segment);
            $segment = str_replace(['ó', 'ò', 'ô', 'õ', 'ö'], 'o', $segment);
            $segment = str_replace(['ú', 'ù', 'û', 'ü'], 'u', $segment);
            $segment = str_replace(['ç'], 'c', $segment);
            $segment = str_replace(['Á', 'À', 'Â', 'Ã', 'Ä'], 'A', $segment);
            $segment = str_replace(['É', 'È', 'Ê', 'Ë'], 'E', $segment);
            $segment = str_replace(['Í', 'Ì', 'Î', 'Ï'], 'I', $segment);
            $segment = str_replace(['Ó', 'Ò', 'Ô', 'Õ', 'Ö'], 'O', $segment);
            $segment = str_replace(['Ú', 'Ù', 'Û', 'Ü'], 'U', $segment);
            $segment = str_replace(['Ç'], 'C', $segment);

            $segment = preg_replace('/[^A-Za-z0-9]+/', '_', $segment) ?? '';
            $segment = trim($segment, '_');

            if ($segment === '') {
                continue;
            }

            $normalized[] = strtoupper($segment);
        }

        return implode('_', $normalized);
    }

    private function normalizeExportValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded !== false ? $encoded : '';
    }

    private function truncateResponse(string $text, int $max = 1000): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }

        if (strlen($trimmed) <= $max) {
            return $trimmed;
        }

        return substr($trimmed, 0, $max) . '...';
    }
}
