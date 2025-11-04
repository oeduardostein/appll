<?php

namespace App\Http\Controllers\Api;

use App\Support\DetranHtmlParser;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class BinController extends Controller
{
    /**
     * Consulta a base BIN utilizando o serviço externo do DETRAN/SP.
     */
    public function __invoke(Request $request): Response
    {
        $placa = $request->query('placa');
        $renavam = $request->query('renavam');
        $captcha = $request->query('captcha');

        if (!$placa || !$renavam || !$captcha) {
            return response()->json(
                ['message' => 'Informe placa, renavam e captcha para realizar a consulta.'],
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

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/bin/cadVeiculo.do',
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

        $cookieDomain = 'www.e-crvsp.sp.gov.br';

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/bin/cadVeiculo.do', [
                'method' => 'pesquisar',
                'placa' => $placa,
                'renavam' => $renavam,
                'captchaResponse' => strtoupper($captcha),
            ]);

        $parsed = DetranHtmlParser::parse($response->body());

        return response()->json(
            $parsed,
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
