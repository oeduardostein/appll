<?php

namespace App\Http\Controllers\Api;

use App\Support\BinHtmlParser;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class FichaCadastralController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $placa = strtoupper(trim((string) $request->query('placa', '')));
        $captchaConsulta = strtoupper(trim((string) $request->query('captcha_consulta', $request->query('captcha', ''))));
        $captchaAndamento = strtoupper(trim((string) $request->query('captcha_andamento', $request->query('captcha', ''))));

        if ($placa === '' || $captchaConsulta === '' || $captchaAndamento === '') {
            return response()->json(
                ['message' => 'Informe placa, captcha_consulta e captcha_andamento para realizar a consulta.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a consulta.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $fichaResponse = $this->requestFichaCadastral(
            token: $token,
            placa: $placa,
            captcha: $captchaConsulta
        );

        if ($fichaResponse['status'] !== Response::HTTP_OK) {
            return response()->json(
                ['message' => $fichaResponse['error'] ?? 'Falha ao consultar ficha cadastral.'],
                $fichaResponse['status']
            );
        }

        $fichaHtml = $fichaResponse['body'];
        $fichaParsed = BinHtmlParser::parse($fichaHtml);

        $numeroFicha = $this->findNormalizedValue($fichaParsed, 'dados_da_ficha_cadastral', 'n_da_ficha');
        $anoFicha = $this->findNormalizedValue($fichaParsed, 'dados_da_ficha_cadastral', 'ano_ficha');

        if ($numeroFicha === null || $anoFicha === null) {
            return response()->json(
                ['message' => 'Não foi possível identificar número e ano da ficha cadastral.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $andamentoResponse = $this->requestAndamentoFicha(
            token: $token,
            numeroFicha: $numeroFicha,
            anoFicha: $anoFicha,
            captcha: $captchaAndamento,
            placa: $placa
        );

        if ($andamentoResponse['status'] !== Response::HTTP_OK) {
            return response()->json(
                ['message' => $andamentoResponse['error'] ?? 'Falha ao consultar andamento do processo.'],
                $andamentoResponse['status']
            );
        }

        $andamentoParsed = BinHtmlParser::parse($andamentoResponse['body']);

        if (
            empty($andamentoParsed['sections']) &&
            ($message = $this->detectHtmlMessage($andamentoResponse['body'])) !== null
        ) {
            return response()->json(
                ['message' => $message],
                Response::HTTP_BAD_GATEWAY
            );
        }

        return response()->json(
            [
                'placa' => $placa,
                'ficha' => [
                    'numero' => $numeroFicha,
                    'ano' => $anoFicha,
                    'payload' => $fichaParsed,
                ],
                'andamento' => $andamentoParsed,
            ],
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * @return array{status:int, body?:string, error?:string}
     */
    private function requestFichaCadastral(string $token, string $placa, string $captcha): array
    {
        $headers = $this->defaultHeaders(
            referer: 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/fichaCadastral.do?modulo=consulta'
        );

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies($this->defaultCookies($token), 'www.e-crvsp.sp.gov.br')
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/fichaCadastral.do', [
                'method' => 'pesquisar',
                'modulo' => 'consulta',
                'urlVoltar' => '',
                'placa' => $placa,
                'numFicha' => '',
                'anoFicha' => '',
                'captchaResponse' => $captcha,
            ]);

        if (!$response->successful()) {
            return [
                'status' => $response->status(),
                'error' => $this->extractErrorMessage($response->body()) ??
                    'Falha ao consultar a ficha cadastral externa.',
            ];
        }

        return [
            'status' => Response::HTTP_OK,
            'body' => $response->body(),
        ];
    }

    /**
     * @return array{status:int, body?:string, error?:string}
     */
    private function requestAndamentoFicha(
        string $token,
        string $numeroFicha,
        string $anoFicha,
        string $captcha,
        string $placa
    ): array {
        $headers = $this->defaultHeaders(
            referer: 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/fichaCadastral.do?modulo=andamento'
        );

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies($this->defaultCookies($token), 'www.e-crvsp.sp.gov.br')
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/fichaCadastral.do', [
                'method' => 'pesquisar',
                'modulo' => 'andamento',
                'urlVoltar' => '',
                'placa' => '',
                'numFicha' => $numeroFicha,
                'anoFicha' => $anoFicha,
                'captchaResponse' => $captcha,
            ]);

        if (!$response->successful()) {
            return [
                'status' => $response->status(),
                'error' => $this->extractErrorMessage($response->body()) ??
                    'Falha ao consultar o andamento do processo externo.',
            ];
        }

        return [
            'status' => Response::HTTP_OK,
            'body' => $response->body(),
        ];
    }

    private function defaultHeaders(string $referer): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => $referer,
            'Sec-Fetch-Dest' => 'frame',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"macOS"',
        ];
    }

    private function defaultCookies(string $token): array
    {
        return [
            'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
            'JSESSIONID' => $token,
        ];
    }

    private function extractErrorMessage(string $body): ?string
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded) && isset($decoded['message'])) {
            $message = trim((string) $decoded['message']);
            return $message === '' ? null : $message;
        }

        return null;
    }

    private function detectHtmlMessage(string $html): ?string
    {
        if (preg_match_all("/errors\\[[^\\]]*\\]\\s*=\\s*'([^']+)'/u", $html, $matches) && !empty($matches[1])) {
            $messages = array_map(static fn ($msg) => html_entity_decode($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches[1]);
            $message = trim(implode(' ', $messages));
            if ($message !== '') {
                return $message;
            }
        }

        if (preg_match("/alert\\s*\\(\\s*'([^']+)'\\s*\\)/u", $html, $matches)) {
            $message = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($message !== '') {
                return $message;
            }
        }

        return null;
    }

    private function findNormalizedValue(array $parsed, string $section, string $field): ?string
    {
        $normalized = $parsed['normalized'] ?? null;
        if (!is_array($normalized)) {
            return null;
        }

        $sectionData = $normalized[$section] ?? null;
        if (!is_array($sectionData)) {
            return null;
        }

        $value = $sectionData[$field] ?? null;
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
