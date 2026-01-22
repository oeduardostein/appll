<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\GravamePesquisaController;
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

class TestePlanilhaGravameController extends Controller
{
    public function index(): View
    {
        return view('admin.teste-planilha-gravame.index');
    }

    /**
     * Consulta gravame ativo e intencao de gravame para uma placa usando captcha automatico.
     */
    public function consultar(Request $request): JsonResponse
    {
        $placa = strtoupper(trim((string) $request->input('placa', '')));
        $renavam = trim((string) $request->input('renavam', ''));

        if ($placa === '') {
            return response()->json([
                'success' => false,
                'error' => 'Placa não informada.',
                'resultado' => 'ERRO NA CONSULTA',
            ], 422);
        }

        $captchaResponse = trim((string) $request->input('captcha_response', ''));

        if ($captchaResponse === '') {
            return response()->json([
                'success' => false,
                'error' => 'Captcha não informado.',
                'resultado' => 'ERRO NA CONSULTA',
            ], 422);
        }

        $debugMode = filter_var($request->input('debug', false), FILTER_VALIDATE_BOOLEAN);

        try {
            $result = $this->queryGravame($placa, $captchaResponse, $debugMode);

            if (isset($result['error'])) {
                $responsePayload = [
                    'success' => false,
                    'error' => $result['error'],
                    'resultado' => 'ERRO NA CONSULTA',
                ];
                if ($debugMode && isset($result['debug'])) {
                    $responsePayload['meta'] = $result['debug'];
                }
                return response()->json($responsePayload);
            }

            $payload = $result['data'] ?? [];
            $hasActive = $this->hasActiveGravame($payload);
            $hasIntention = $this->hasIntencaoGravame($payload);

            $resultadoStatus = ($hasActive || $hasIntention) ? 'nao_liberado' : 'liberado';
            $resultadoLabel = $resultadoStatus === 'liberado' ? 'LIBERADO' : 'NÃO LIBERADO';
            $resultadoDetalhe = $this->buildResultadoDetalhe($hasActive, $hasIntention);

            $responsePayload = [
                'success' => true,
                'placa' => $placa,
                'renavam' => $renavam,
                'gravame_ativo' => $hasActive,
                'intencao_gravame' => $hasIntention,
                'resultado' => $resultadoLabel,
                'resultado_detalhe' => $resultadoDetalhe,
                'resultado_status' => $resultadoStatus,
            ];

            if ($debugMode && isset($result['debug'])) {
                $responsePayload['meta'] = $result['debug'];
            }

            return response()->json($responsePayload);
        } catch (\Exception $e) {
            Log::error('TestePlanilhaGravame: Erro na consulta', [
                'placa' => $placa,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao consultar: ' . $e->getMessage(),
                'resultado' => 'ERRO NA CONSULTA',
            ]);
        }
    }

    /**
     * Exporta a planilha preenchida com os resultados.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $dados = $request->input('dados', []);
        $tipo = (string) $request->input('tipo', 'resultado');

        $columns = [];
        $requestColumns = $request->input('colunas', []);
        if (is_array($requestColumns)) {
            foreach ($requestColumns as $column) {
                if (!is_string($column)) {
                    continue;
                }
                $column = trim($column);
                if ($column === '') {
                    continue;
                }
                if (in_array($column, $columns, true)) {
                    continue;
                }
                $columns[] = $column;
            }
        }

        if ($columns === []) {
            $columns = ['PLACA', 'RENAVAM', 'NOME'];
        }

        $resultadoIndex = null;
        foreach ($columns as $index => $column) {
            if (mb_strtoupper($column) === 'RESULTADO') {
                $resultadoIndex = $index;
                break;
            }
        }

        if ($resultadoIndex === null) {
            $columns[] = 'RESULTADO';
            $resultadoIndex = count($columns) - 1;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resultado');

        foreach ($columns as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0B4EA2'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
        ];
        $headerEndColumn = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle("A1:{$headerEndColumn}1")->applyFromArray($headerStyle);

        $rowIndex = 2;
        foreach ($dados as $linha) {
            $resultado = trim((string) ($linha['resultado'] ?? ''));
            $detalhe = trim((string) ($linha['resultado_detalhe'] ?? ''));
            $resultadoFinal = $resultado;
            if ($detalhe !== '') {
                $resultadoFinal = $resultado !== '' ? $resultado . ' - ' . $detalhe : $detalhe;
            }

            foreach ($columns as $colIndex => $column) {
                if ($colIndex === $resultadoIndex) {
                    $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex, $resultadoFinal);
                    continue;
                }

                $sheet->setCellValueByColumnAndRow(
                    $colIndex + 1,
                    $rowIndex,
                    $linha[$column] ?? ''
                );
            }

            $rowIndex++;
        }

        for ($colIndex = 1; $colIndex <= count($columns); $colIndex++) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $filenamePrefix = match ($tipo) {
            'liberados' => 'gravame_liberados',
            'com_gravame' => 'gravame_com_gravame',
            default => 'gravame_resultado',
        };
        $filename = $filenamePrefix . '_' . date('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Consulta o endpoint de gravame reaproveitando a logica da base estadual.
     *
     * @param bool $captureDebug Quando verdadeiro, inclui detalhes do payload/resposta para diagnostico.
     * @return array<string, mixed>
     */
    private function queryGravame(string $placa, string $captchaResponse, bool $captureDebug = false): array
    {
        $payload = [
            'placa' => preg_replace('/[^A-Za-z0-9]/', '', $placa),
            'captcha' => strtoupper($captchaResponse),
        ];

        $targetUrl = '/api/gravame';
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

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = app(GravamePesquisaController::class)->__invoke($apiRequest);
        $status = $response->getStatusCode();
        $body = $response->getContent();
        $decoded = json_decode($body, true);

        if ($captureDebug && $debugInfo !== null) {
            $debugInfo['response'] = [
                'status' => $status,
                'body' => $this->truncateResponse($body),
            ];
        }

        if ($status !== SymfonyResponse::HTTP_OK) {
            $errorMessage = $decoded['message'] ?? 'Erro ao consultar gravame.';
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
     * Indica se ha gravame ativo no payload retornado.
     */
    private function hasActiveGravame(array $payload): bool
    {
        $gravames = $payload['gravames'] ?? null;
        if (!is_array($gravames)) {
            return false;
        }

        $gravamesDatas = $payload['gravames_datas'] ?? null;
        if (!is_array($gravamesDatas) && isset($gravames['datas']) && is_array($gravames['datas'])) {
            $gravamesDatas = $gravames['datas'];
        }

        $fields = [
            $gravames['restricao_financeira'] ?? null,
            $gravames['nome_agente'] ?? null,
            $gravames['arrendatario'] ?? null,
            $gravames['cnpj_cpf_financiado'] ?? null,
            is_array($gravamesDatas) ? ($gravamesDatas['inclusao_financiamento'] ?? null) : null,
        ];

        foreach ($fields as $value) {
            if ($this->isMeaningfulValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indica se ha intencao de gravame no payload retornado.
     */
    private function hasIntencaoGravame(array $payload): bool
    {
        $intencao = $payload['intencao_gravame'] ?? null;
        if (!is_array($intencao)) {
            return false;
        }

        $fields = [
            $intencao['restricao_financeira'] ?? null,
            $intencao['agente_financeiro'] ?? null,
            $intencao['nome_financiado'] ?? null,
            $intencao['cnpj_cpf'] ?? null,
            $intencao['data_inclusao'] ?? null,
        ];

        foreach ($fields as $value) {
            if ($this->isMeaningfulValue($value)) {
                return true;
            }
        }

        return false;
    }

    private function isMeaningfulValue(?string $value): bool
    {
        $normalized = $this->normalizeStatusText($value);
        if ($normalized === '') {
            return false;
        }

        $emptyValues = [
            'NADA CONSTA',
            'NAO CONSTA',
            'NAO CONSTAM',
        ];

        return !in_array($normalized, $emptyValues, true);
    }

    private function normalizeStatusText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        if ($value === '') {
            return '';
        }

        $map = [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'Ê' => 'E', 'È' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
            'á' => 'A', 'à' => 'A', 'â' => 'A', 'ã' => 'A', 'ä' => 'A',
            'é' => 'E', 'ê' => 'E', 'è' => 'E', 'ë' => 'E',
            'í' => 'I', 'ì' => 'I', 'î' => 'I', 'ï' => 'I',
            'ó' => 'O', 'ò' => 'O', 'ô' => 'O', 'õ' => 'O', 'ö' => 'O',
            'ú' => 'U', 'ù' => 'U', 'û' => 'U', 'ü' => 'U',
            'ç' => 'C',
        ];

        $value = strtr($value, $map);

        return mb_strtoupper($value);
    }

    private function buildResultadoDetalhe(bool $hasActive, bool $hasIntention): string
    {
        $parts = [];

        if ($hasActive) {
            $parts[] = 'POSSUI GRAVAME ATIVO';
        } else {
            $parts[] = 'NÃO CONSTA GRAVAME ATIVO';
        }

        if ($hasIntention) {
            $parts[] = 'POSSUI INTENÇÃO DE GRAVAME';
        } else {
            $parts[] = 'NÃO CONSTA INTENÇÃO DE GRAVAME';
        }

        return implode(' | ', $parts);
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
