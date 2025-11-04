<?php

namespace App\Http\Controllers\Api;

use App\Support\BinHtmlParser;
use App\Support\FichaCadastralHttp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class FichaCadastralAndamentoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $numeroFicha = trim((string) $request->query('numero_ficha', $request->query('num_ficha', '')));
        $anoFicha = trim((string) $request->query('ano_ficha', $request->query('anoFicha', '')));
        $captcha = strtoupper(trim((string) $request->query('captcha', '')));
        $placa = strtoupper(trim((string) $request->query('placa', '')));

        if ($numeroFicha === '' || $anoFicha === '' || $captcha === '') {
            return response()->json(
                ['message' => 'Informe numero_ficha, ano_ficha e captcha para consultar o andamento.'],
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

        $response = Http::withHeaders(
            FichaCadastralHttp::headers(
                'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/fichaCadastral.do?modulo=andamento'
            )
        )
            ->withOptions(['verify' => false])
            ->withCookies(FichaCadastralHttp::cookies($token), 'www.e-crvsp.sp.gov.br')
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/fichaCadastral.do', [
                'method' => 'pesquisar',
                'modulo' => 'andamento',
                'urlVoltar' => '',
                'placa' => $placa,
                'numFicha' => $numeroFicha,
                'anoFicha' => $anoFicha,
                'captchaResponse' => $captcha,
            ]);

        if (!$response->successful()) {
            return response()->json(
                ['message' => FichaCadastralHttp::extractJsonMessage($response->body()) ??
                    'Falha ao consultar o andamento do processo externo.'],
                $response->status()
            );
        }

        $parsed = BinHtmlParser::parse($response->body());
        if (
            empty($parsed['sections']) &&
            ($message = FichaCadastralHttp::extractHtmlMessage($response->body())) !== null
        ) {
            return response()->json(
                ['message' => $message],
                Response::HTTP_BAD_GATEWAY
            );
        }

        return response()->json(
            [
                'numero_ficha' => $numeroFicha,
                'ano_ficha' => $anoFicha,
                'placa' => $placa !== '' ? $placa : null,
                'payload' => $parsed,
            ],
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
