<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CaptchaException;
use App\Http\Controllers\Controller;
use App\Services\DetranCaptchaClient;
use App\Support\DetranHtmlParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferVerificationController extends Controller
{
    /**
     * Display the transfer verification page.
     */
    public function index(): View
    {
        return view('admin.transfer-verification.index');
    }

    /**
     * Process the uploaded XLSX file and return the list of rows.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        $headerRow = $worksheet->getRowIterator(1, 1)->current();
        $headers = [];
        foreach ($headerRow->getCellIterator() as $cell) {
            $headers[] = mb_strtoupper(trim((string) $cell->getValue()));
        }

        $placaIndex = array_search('PLACA', $headers);
        $renavamIndex = array_search('RENAVAM', $headers);
        $nomeIndex = array_search('NOME', $headers);

        if ($placaIndex === false || $renavamIndex === false || $nomeIndex === false) {
            return response()->json([
                'success' => false,
                'message' => 'O arquivo deve conter as colunas: PLACA, RENAVAM e NOME',
            ], 422);
        }

        $rowIterator = $worksheet->getRowIterator(2);
        $index = 0;

        foreach ($rowIterator as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            $placa = $cells[$placaIndex] ?? '';
            $renavam = $cells[$renavamIndex] ?? '';
            $nome = $cells[$nomeIndex] ?? '';

            if (empty($placa) && empty($renavam)) {
                continue;
            }

            $rows[] = [
                'index' => $index,
                'placa' => mb_strtoupper($placa),
                'renavam' => $renavam,
                'nome' => mb_strtoupper($nome),
                'status' => 'pending',
            ];

            $index++;
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma linha válida encontrada no arquivo.',
            ], 422);
        }

        // Store rows in session for later download
        Session::put('transfer_verification_rows', $rows);
        Session::put('transfer_verification_results', []);

        return response()->json([
            'success' => true,
            'rows' => $rows,
            'total' => count($rows),
        ]);
    }

    /**
     * Verify a single row by querying the state database.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
            'placa' => 'required|string',
            'renavam' => 'nullable|string',
            'nome' => 'required|string',
        ]);

        $index = $request->input('index');
        $placa = mb_strtoupper($request->input('placa'));
        $renavam = $request->input('renavam', '');
        $nomeInformado = $this->normalizeString($request->input('nome'));

        // Step 1: Solve captcha automatically
        $captchaSolution = $this->solveCaptcha();

        if ($captchaSolution === null) {
            return $this->updateResultAndRespond($index, [
                'success' => false,
                'error' => 'Falha ao resolver captcha',
                'nome_proprietario' => null,
                'data_crlv' => null,
                'obs' => 'ERRO: Captcha',
            ]);
        }

        // Step 2: Query the state database
        $consultaResult = $this->queryBaseEstadual($placa, $renavam, $captchaSolution);

        if (!$consultaResult['success']) {
            return $this->updateResultAndRespond($index, [
                'success' => false,
                'error' => $consultaResult['error'],
                'nome_proprietario' => null,
                'data_crlv' => null,
                'obs' => 'ERRO: ' . $consultaResult['error'],
            ]);
        }

        $nomeProprietario = $consultaResult['nome_proprietario'] ?? '';
        $dataCrlv = $consultaResult['data_crlv'] ?? '';

        // Step 3: Compare names
        $nomeProprietarioNormalizado = $this->normalizeString($nomeProprietario);
        $obs = '';

        if ($nomeInformado !== '' && $nomeProprietarioNormalizado !== '') {
            if ($this->namesMatch($nomeInformado, $nomeProprietarioNormalizado)) {
                $obs = 'Transferência NÃO CONCLUÍDA';
            }
        }

        return $this->updateResultAndRespond($index, [
            'success' => true,
            'nome_proprietario' => $nomeProprietario,
            'data_crlv' => $dataCrlv,
            'obs' => $obs,
        ]);
    }

    /**
     * Download the results as an XLSX file.
     */
    public function download(): StreamedResponse
    {
        $rows = Session::get('transfer_verification_rows', []);
        $results = Session::get('transfer_verification_results', []);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'PLACA');
        $sheet->setCellValue('B1', 'RENAVAM');
        $sheet->setCellValue('C1', 'NOME');
        $sheet->setCellValue('D1', 'NOME_PROPRIETARIO');
        $sheet->setCellValue('E1', 'DATA_CRLV');
        $sheet->setCellValue('F1', 'OBS');

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Data rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $result = $results[$row['index']] ?? [];

            $sheet->setCellValue('A' . $rowNum, $row['placa']);
            $sheet->setCellValue('B' . $rowNum, $row['renavam']);
            $sheet->setCellValue('C' . $rowNum, $row['nome']);
            $sheet->setCellValue('D' . $rowNum, $result['nome_proprietario'] ?? '');
            $sheet->setCellValue('E' . $rowNum, $result['data_crlv'] ?? '');
            $sheet->setCellValue('F' . $rowNum, $result['obs'] ?? '');

            // Highlight rows with "NÃO CONCLUÍDA"
            if (str_contains($result['obs'] ?? '', 'NÃO CONCLUÍDA')) {
                $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEE2E2'],
                    ],
                ]);
            }

            // Highlight error rows
            if (str_contains($result['obs'] ?? '', 'ERRO:')) {
                $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7'],
                    ],
                ]);
            }

            $rowNum++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'verificacao_transferencias_' . date('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Solve captcha using 2Captcha service.
     */
    private function solveCaptcha(): ?string
    {
        $apiKey = config('services.twocaptcha.key');

        if (!$apiKey) {
            Log::warning('TransferVerification: 2Captcha API key not configured');
            return null;
        }

        try {
            $captchaClient = app(DetranCaptchaClient::class);
            $captcha = $captchaClient->fetch();
        } catch (CaptchaException $e) {
            Log::warning('TransferVerification: Failed to fetch captcha', ['error' => $e->getMessage()]);
            return null;
        }

        $captchaBase64 = base64_encode($captcha['body']);

        $uploadResponse = Http::asForm()->post('https://2captcha.com/in.php', [
            'key' => $apiKey,
            'method' => 'base64',
            'body' => $captchaBase64,
            'json' => 1,
        ]);

        if (!$uploadResponse->successful()) {
            Log::warning('TransferVerification: Failed to upload captcha to 2Captcha');
            return null;
        }

        $uploadPayload = $uploadResponse->json();
        if (!is_array($uploadPayload) || ($uploadPayload['status'] ?? 0) !== 1) {
            Log::warning('TransferVerification: 2Captcha upload error', ['response' => $uploadPayload]);
            return null;
        }

        $captchaId = (string) $uploadPayload['request'];

        // Poll for solution
        $maxAttempts = 12;
        $delaySeconds = 5;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep($delaySeconds);

            $resultResponse = Http::asForm()->get('https://2captcha.com/res.php', [
                'key' => $apiKey,
                'action' => 'get',
                'id' => $captchaId,
                'json' => 1,
            ]);

            if (!$resultResponse->successful()) {
                continue;
            }

            $resultPayload = $resultResponse->json();
            if (!is_array($resultPayload)) {
                continue;
            }

            if (($resultPayload['status'] ?? 0) === 1) {
                return (string) $resultPayload['request'];
            }

            $error = $resultPayload['request'] ?? '';
            if ($error !== 'CAPCHA_NOT_READY') {
                break;
            }
        }

        return null;
    }

    /**
     * Query the state database with the given parameters.
     */
    private function queryBaseEstadual(string $placa, string $renavam, string $captcha): array
    {
        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (!$token) {
            return [
                'success' => false,
                'error' => 'Token não configurado',
            ];
        }

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseEstadual.do',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
        ];

        $cookieDomain = 'www.e-crvsp.sp.gov.br';

        try {
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->withOptions(['verify' => false])
                ->withCookies([
                    'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                    'JSESSIONID' => $token,
                ], $cookieDomain)
                ->asForm()
                ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseEstadual.do', [
                    'method' => 'pesquisar',
                    'placa' => $placa,
                    'renavam' => $renavam,
                    'municipio' => '0',
                    'chassi' => '',
                    'captchaResponse' => strtoupper($captcha),
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Falha na consulta (HTTP ' . $response->status() . ')',
                ];
            }
        } catch (\Exception $e) {
            Log::error('TransferVerification: Query error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Erro de conexão',
            ];
        }

        $body = $response->body();

        // Check for errors in response
        $errors = $this->extractErrors($body);
        if (!empty($errors)) {
            return [
                'success' => false,
                'error' => $errors[0],
            ];
        }

        // Parse the response
        $parsed = DetranHtmlParser::parse($body);

        $nomeProprietario = $parsed['proprietario']['nome'] ?? null;
        $dataCrlv = $parsed['crv_crlv_atualizacao']['data_licenciamento'] ?? null;

        if (!$nomeProprietario) {
            return [
                'success' => false,
                'error' => 'Proprietário não encontrado',
            ];
        }

        return [
            'success' => true,
            'nome_proprietario' => $nomeProprietario,
            'data_crlv' => $dataCrlv,
        ];
    }

    /**
     * Extract errors from the HTML response.
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
     * Normalize a string for comparison.
     */
    private function normalizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        // Remove accents
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

        $value = strtr($value, $map);

        return mb_strtoupper($value);
    }

    /**
     * Check if two names match (considering partial matches).
     */
    private function namesMatch(string $name1, string $name2): bool
    {
        // Exact match
        if ($name1 === $name2) {
            return true;
        }

        // Check if one contains the other (for partial name matches)
        if (str_contains($name1, $name2) || str_contains($name2, $name1)) {
            return true;
        }

        return false;
    }

    /**
     * Update the result in session and return JSON response.
     */
    private function updateResultAndRespond(int $index, array $result): JsonResponse
    {
        $results = Session::get('transfer_verification_results', []);
        $results[$index] = $result;
        Session::put('transfer_verification_results', $results);

        return response()->json($result);
    }
}
