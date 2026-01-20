<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseEstadualController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestePlanilhaController extends Controller
{
    public function index(): View
    {
        return view('admin.teste-planilha.index');
    }

    /**
     * Consulta a base estadual para uma placa/renavam específico usando captcha automático.
     */
    public function consultar(Request $request): JsonResponse
    {
        $placa = strtoupper(trim((string) $request->input('placa', '')));
        $renavam = trim((string) $request->input('renavam', ''));
        $nomeVerificacao = $this->normalizeName((string) $request->input('nome_verificacao', ''));

        if ($placa === '') {
            return response()->json([
                'success' => false,
                'error' => 'Placa não informada.',
            ], 422);
        }

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Token de sessão não configurado.',
            ], 500);
        }

        $captchaResponse = trim((string) $request->input('captcha_response', ''));

        if ($captchaResponse === '') {
            return response()->json([
                'success' => false,
                'error' => 'Captcha não informado.',
                'nome_proprietario' => null,
                'data_crlv' => null,
                'obs' => 'ERRO NA CONSULTA',
            ], 422);
        }

        $debugMode = filter_var($request->input('debug', false), FILTER_VALIDATE_BOOLEAN);

        try {
            $result = $this->queryBaseEstadual($placa, $renavam, $token, $captchaResponse, $debugMode);

            if (isset($result['error'])) {
                $responsePayload = [
                    'success' => false,
                    'error' => $result['error'],
                    'nome_proprietario' => null,
                    'data_crlv' => null,
                    'obs' => 'ERRO NA CONSULTA',
                ];
                if ($debugMode && isset($result['debug'])) {
                    $responsePayload['meta'] = $result['debug'];
                }
                return response()->json($responsePayload);
            }

            $parsed = $result['data'] ?? $result;
            $nomeProprietario = $parsed['proprietario']['nome'] ?? null;
            $dataCrlv = $parsed['crv_crlv_atualizacao']['data_licenciamento'] ?? null;

            $obs = '';
            if ($nomeProprietario && $nomeVerificacao !== '') {
                $nomeProprietarioNorm = $this->normalizeName($nomeProprietario);
                if ($nomeProprietarioNorm === $nomeVerificacao) {
                    $obs = 'transferência NÃO CONCLUIDA';
                }
            }

            $responsePayload = [
                'success' => true,
                'nome_proprietario' => $nomeProprietario,
                'data_crlv' => $dataCrlv,
                'obs' => $obs,
            ];

            if ($debugMode && isset($result['debug'])) {
                $responsePayload['meta'] = $result['debug'];
            }

            return response()->json($responsePayload);
        } catch (\Exception $e) {
            Log::error('TestePlanilha: Erro na consulta', [
                'placa' => $placa,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao consultar: ' . $e->getMessage(),
                'nome_proprietario' => null,
                'data_crlv' => null,
                'obs' => 'ERRO NA CONSULTA',
            ]);
        }
    }

    /**
     * Exporta a planilha preenchida com os resultados.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $dados = $request->input('dados', []);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resultado');

        // Cabeçalhos
        $headers = ['PLACA', 'RENAVAM', 'NOME', 'NOME PROPRIETARIO', 'DATA CRLV', 'OBS'];
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        // Estilo do cabeçalho
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
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Dados
        $rowIndex = 2;
        foreach ($dados as $linha) {
            $sheet->setCellValueByColumnAndRow(1, $rowIndex, $linha['placa'] ?? '');
            $sheet->setCellValueByColumnAndRow(2, $rowIndex, $linha['renavam'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $rowIndex, $linha['nome'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $rowIndex, $linha['nome_proprietario'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $rowIndex, $linha['data_crlv'] ?? '');
            $sheet->setCellValueByColumnAndRow(6, $rowIndex, $linha['obs'] ?? '');
            $rowIndex++;
        }

        // Auto-dimensionar colunas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'resultado_verificacao_' . date('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Consulta a base estadual do Detran SP usando um captcha resolvido.
     *
     * @param bool $captureDebug Quando verdadeiro, inclui detalhes do payload/resposta para diagnóstico.
     */
    private function queryBaseEstadual(
        string $placa,
        string $renavam,
        string $token,
        string $captchaResponse,
        bool $captureDebug = false
    ): array {
        $payload = [
            'placa' => $placa,
            'captcha' => strtoupper($captchaResponse),
        ];

        if ($renavam !== '') {
            $payload['renavam'] = $renavam;
        }

        $targetUrl = '/api/base-estadual';
        $apiRequest = Request::create($targetUrl, 'GET', $payload);

        $debugInfo = null;
        if ($captureDebug) {
            $debugInfo = [
                'token' => $this->maskToken($token),
                'captcha' => $payload['captcha'],
                'payload' => $payload,
                'url' => url($targetUrl),
                'response' => null,
            ];
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = app()->call([BaseEstadualController::class, '__invoke'], ['request' => $apiRequest]);
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
            $errorMessage = $decoded['message'] ?? 'Erro ao consultar a base estadual.';
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
     * Extrai mensagens de erro do HTML de resposta do Detran.
     */
    private function extractErrors(string $html): array
    {
        if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
            $html = @mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        $errors = [];
        if (preg_match_all('/errors\[errors\.length\]\s*=\s*[\'"]([^\'"]+)[\'"]\s*;?/iu', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $errors[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $errors;
    }

    /**
     * Normaliza um nome para comparação (remove acentos, converte para minúsculas, etc).
     */
    private function normalizeName(?string $name): string
    {
        if ($name === null || $name === '') {
            return '';
        }

        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

        $map = [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'Ê' => 'E', 'È' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];

        $name = strtr($name, $map);

        return mb_strtolower($name);
    }

    private function maskToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        $length = strlen($token);
        if ($length <= 6) {
            return str_repeat('*', max(0, $length - 2)) . substr($token, -2);
        }

        return substr($token, 0, 3) . str_repeat('*', max(0, $length - 6)) . substr($token, -3);
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
