<?php

namespace App\Http\Controllers\Api;

use App\Support\DetranHtmlParser;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (!$token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a consulta.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/consultarIntencaoVenda.do',
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

        $renavam = trim($data['renavam']);
        $placa = strtoupper(trim($data['placa']));
        $captcha = strtoupper(trim($data['captcha']));

        $form = [
            'method' => 'pesquisar',
            'renavam' => $renavam,
            'placa' => $placa,
            'codigoEstadoIntencaoVenda' => '0',
            'numeroAtpv' => '',
            'dataInicioPesqSTR' => '',
            'horaInicioPesq' => '',
            'dataFimPesqSTR' => '',
            'horaFimPesq' => '',
            'captcha' => $captcha,
        ];

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], 'www.e-crvsp.sp.gov.br')
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/consultarIntencaoVenda.do', $form);

        $body = $response->body();
        $errors = $this->extractErrors($body);

        if (!empty($errors)) {
            return response()->json(
                ['message' => $errors[0], 'detalhes' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $parsed = DetranHtmlParser::parse($body);

        if (empty($parsed['comunicacao_vendas'])) {
            return response()->json(
                ['message' => 'Nenhuma comunicação de venda encontrada para os dados informados.'],
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
            $responseBody,
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * @return string[]
     */
    private function extractErrors(string $html): array
    {
        if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
            $html = @mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        $errors = [];
        if (preg_match_all('/errors\[errors\.length\]\s*=\s*[\'"]([^\'"]+)[\'"]\s*;?/u', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $errors[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $errors;
    }
}
