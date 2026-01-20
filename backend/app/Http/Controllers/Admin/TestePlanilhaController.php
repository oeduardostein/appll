<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DetranHtmlParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestePlanilhaController extends Controller
{
    public function index(): View
    {
        return view('admin.teste-planilha.index');
    }

    /**
     * Consulta a base estadual para uma placa/renavam específico (sem captcha para admin).
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

        try {
            $result = $this->queryBaseEstadual($placa, $renavam, $token);

            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'nome_proprietario' => null,
                    'data_crlv' => null,
                    'obs' => 'ERRO NA CONSULTA',
                ]);
            }

            $nomeProprietario = $result['proprietario']['nome'] ?? null;
            $dataCrlv = $result['crv_crlv_atualizacao']['data_licenciamento'] ?? null;

            $obs = '';
            if ($nomeProprietario && $nomeVerificacao !== '') {
                $nomeProprietarioNorm = $this->normalizeName($nomeProprietario);
                if ($nomeProprietarioNorm === $nomeVerificacao) {
                    $obs = 'transferência NÃO CONCLUIDA';
                }
            }

            return response()->json([
                'success' => true,
                'nome_proprietario' => $nomeProprietario,
                'data_crlv' => $dataCrlv,
                'obs' => $obs,
            ]);
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
     * Consulta a base estadual do Detran SP.
     */
    private function queryBaseEstadual(string $placa, string $renavam, string $token): array
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseEstadual.do',
            'Sec-Fetch-Dest' => 'frame',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
        ];

        $cookieDomain = 'www.e-crvsp.sp.gov.br';

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
                'captchaResponse' => '',
            ]);

        if (!$response->successful()) {
            return ['error' => 'Falha na comunicação com o Detran (HTTP ' . $response->status() . ')'];
        }

        $body = $response->body();

        $errors = $this->extractErrors($body);
        if (!empty($errors)) {
            return ['error' => $errors[0]];
        }

        return DetranHtmlParser::parse($body);
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
}
