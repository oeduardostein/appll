<?php

namespace App\Http\Controllers\Api;

use App\Support\DetranHtmlParser;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ConsultaIntencaoVendaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'renavam' => ['required', 'string', 'max:20'],
            'placa' => ['required', 'string', 'max:10'],
            'captcha' => ['required', 'string', 'max:12'],
        ]);

        $renavam = trim($data['renavam']);
        $placa = strtoupper(trim($data['placa']));
        $captcha = strtoupper(trim($data['captcha']));

        $hosts = ['e-crvsp.sp.gov.br', 'www.e-crvsp.sp.gov.br'];
        $lastException = null;
        $response = null;
        $baseUrl = '';
        $step = '';

        foreach ($hosts as $host) {
            try {
                $result = $this->performConsulta($host, $renavam, $placa, $captcha);
                $response = $result['response'];
                $baseUrl = $result['base_url'];
                $step = $result['step'];
                break;
            } catch (ConnectionException $e) {
                $lastException = $e;
                Log::warning('Consulta intenção venda: falha de conexão', [
                    'host' => $host,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $response) {
            return response()->json(
                [
                    'ok' => false,
                    'message' => 'Falha ao consultar o serviço. Host indisponível.',
                    'detalhes' => $lastException ? [$lastException->getMessage()] : [],
                ],
                Response::HTTP_BAD_GATEWAY
            );
        }

        if (! $response->successful()) {
            $stats = $response->handlerStats();
            $finalUrl = is_array($stats) ? ($stats['url'] ?? $baseUrl) : $baseUrl;
            Log::warning('Consulta intenção venda: resposta não-ok', [
                'status' => $response->status(),
                'url' => $finalUrl,
                'step' => $step,
                'body_preview' => mb_substr($response->body(), 0, 300),
            ]);

            return response()->json(
                [
                    'ok' => false,
                    'message' => 'Falha ao consultar o serviço. Sessão inválida ou acesso negado.',
                    'status' => $response->status(),
                ],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $body = $response->body();
        $body = $this->normalizeEncoding($body);
        $errors = $this->extractErrors($body);

        $stats = $response->handlerStats();
        $finalUrl = is_array($stats) ? ($stats['url'] ?? $baseUrl) : $baseUrl;
        Log::info('Consulta intenção venda: resposta', [
            'status' => $response->status(),
            'url' => $finalUrl,
            'step' => $step,
            'body_preview' => mb_substr($body, 0, 300),
        ]);

        if (! empty($errors)) {
            return response()->json(
                ['ok' => false, 'message' => $errors[0], 'detalhes' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $parsed = DetranHtmlParser::parse($body);
        $hasFormMarker = stripos($body, 'Consultar Intenção de Venda') !== false;

        if (empty($parsed['comunicacao_vendas'])) {
            if (! $hasFormMarker) {
                return response()->json(
                    [
                        'ok' => false,
                        'message' => 'Falha ao consultar o serviço. Sessão inválida ou fluxo mudou.',
                    ],
                    Response::HTTP_BAD_GATEWAY
                );
            }

            return response()->json(
                [
                    'ok' => false,
                    'message' => 'Nenhuma comunicação de venda encontrada para os dados informados.',
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $responseBody = array_merge(
            [
                'consulta' => [
                    'renavam' => $renavam,
                    'placa' => $placa,
                    'codigo_estado_intencao_venda' => '0',
                ],
            ],
            $parsed
        );

        return response()->json(
            [
                'ok' => true,
                'data' => $responseBody,
            ],
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * @return array{response:\Illuminate\Http\Client\Response, base_url:string}
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    private function performConsulta(string $host, string $renavam, string $placa, string $captcha): array
    {
        $baseUrl = "https://{$host}/gever/GVR/emissao/consultarIntencaoVenda.do";
        $jar = new CookieJar();
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => "https://{$host}",
            'Referer' => $baseUrl,
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
        ];

        $initialResponse = Http::withOptions([
            'cookies' => $jar,
            'allow_redirects' => true,
            'verify' => true,
        ])
            ->withHeaders($headers)
            ->get($baseUrl);

        if (! $initialResponse->successful()) {
            return [
                'response' => $initialResponse,
                'base_url' => $baseUrl,
                'step' => 'get',
            ];
        }

        $form = [
            'method' => 'pesquisar',
            'renavam' => $renavam,
            'placa' => $placa,
            'codigoEstadoIntencaoVenda' => '0',
            'numeroAtpve' => '',
            'dataInicioPesqSTR' => '',
            'horaInicioPesq' => '',
            'dataFimPesqSTR' => '',
            'horaFimPesq' => '',
            'captcha' => $captcha,
        ];

        $response = Http::withOptions([
            'cookies' => $jar,
            'allow_redirects' => true,
            'verify' => true,
        ])
            ->withHeaders($headers)
            ->asForm()
            ->post($baseUrl, $form);

        return [
            'response' => $response,
            'base_url' => $baseUrl,
            'step' => 'post',
        ];
    }

    /**
     * @return string[]
     */
    private function extractErrors(string $html): array
    {
        $errors = [];
        if (preg_match_all('/errors\[errors\.length\]\s*=\s*[\'"]([^\'"]+)[\'"]\s*;?/u', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $errors[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $errors;
    }

    private function normalizeEncoding(string $html): string
    {
        if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
            $html = @mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        return $html;
    }
}
