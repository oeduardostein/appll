<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ImpressaoCrlvController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $debugMode = filter_var($request->query('debug', false), FILTER_VALIDATE_BOOLEAN) && (bool) config('app.debug');

        $placa = strtoupper((string) $request->query('placa', ''));
        $placa = preg_replace('/[^A-Za-z0-9]/', '', $placa) ?? '';

        $renavam = (string) $request->query('renavam', '');
        $renavam = preg_replace('/\\D/', '', $renavam) ?? '';

        $cpf = (string) $request->query('cpf', '');
        $cpf = preg_replace('/\\D/', '', $cpf) ?? '';

        $cnpj = (string) $request->query('cnpj', '');
        $cnpj = preg_replace('/\\D/', '', $cnpj) ?? '';

        $captcha = (string) $request->query('captchaResponse', '');
        if ($captcha === '') {
            $captcha = (string) $request->query('captcha', '');
        }
        $captcha = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $captcha) ?? '');

        $opcaoPesquisa = (string) $request->query('opcaoPesquisa', '');
        $opcaoPesquisa = preg_replace('/\\D/', '', $opcaoPesquisa) ?? '';

        if ($placa === '' || $renavam === '' || $captcha === '' || $opcaoPesquisa === '') {
            return response()->json(
                ['message' => 'Informe placa, renavam, captchaResponse (ou captcha) e opcaoPesquisa.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($opcaoPesquisa === '1' && $cpf === '') {
            return response()->json(
                ['message' => 'Informe cpf quando opcaoPesquisa=1.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($opcaoPesquisa === '2' && $cnpj === '') {
            return response()->json(
                ['message' => 'Informe cnpj quando opcaoPesquisa=2.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Token no admin_settings.value
        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a consulta.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Headers/Cookies
        $headers = [
            'Accept'                    => 'application/pdf,text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language'           => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control'             => 'max-age=0',
            'Connection'                => 'keep-alive',
            'Content-Type'              => 'application/x-www-form-urlencoded',
            'Origin'                    => 'https://www.e-crvsp.sp.gov.br',
            'Referer'                   => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoCrlv.do',
            'Sec-Fetch-Dest'            => 'frame',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => 'same-origin',
            'Sec-Fetch-User'            => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent'                => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'sec-ch-ua'                 => '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile'          => '?0',
            'sec-ch-ua-platform'        => '"macOS"',
        ];
        $cookieDomain = 'www.e-crvsp.sp.gov.br';

        // Form com envio condicional de CPF/CNPJ
        $form = [
            'method'          => 'pesquisar',
            'placa'           => $placa,
            'renavam'         => $renavam,
            'opcaoPesquisa'   => (string) $opcaoPesquisa,
            'captchaResponse' => $captcha,
            'cpf'             => '',
            'cnpj'            => '',
        ];
        if ((string) $opcaoPesquisa === '1') {
            $form['cpf']  = $cpf;
        } elseif ((string) $opcaoPesquisa === '2') {
            $form['cnpj'] = $cnpj;
        }
        // se a tela aceitar ambos sempre, pode remover esse condicional

        try {
            $cookieTimestamp = now()->setTimezone('America/Sao_Paulo')->format('D M d Y H:i:s');
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->withOptions(['verify' => false]) // REMOVER em produção
                ->withCookies([
                    'naoExibirPublic' => 'sim',
                    'dataUsuarPublic' => $cookieTimestamp . ' GMT-0300 (Horário Padrão de Brasília)',
                    'JSESSIONID'      => $token,
                ], $cookieDomain)
                ->asForm()
                ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoCrlv.do', $form);

            if (!$response->successful()) {
                $status = $response->status();
                $bodyPreview = substr($response->body(), 0, 800);

                Log::warning('CRLV: falha no POST de pesquisa', [
                    'status' => $status,
                    'placa' => $placa,
                    'renavam' => $renavam,
                    'opcaoPesquisa' => $opcaoPesquisa,
                    'body_preview' => $bodyPreview,
                ]);

                $message = match (true) {
                    $status >= 300 && $status < 400 => 'Sessão expirada ou redirecionada. Atualize o token e tente novamente.',
                    $status === 401 || $status === 403 => 'Sessão expirada ou acesso negado. Atualize o token e tente novamente.',
                    $status === 429 => 'Muitas tentativas. Aguarde e tente novamente.',
                    $status >= 500 => 'O serviço do DETRAN está temporariamente indisponível. Tente novamente em alguns instantes.',
                    default => 'Falha ao consultar a emissão/impressão do CRLV.',
                };

                $payload = ['message' => $message];
                if ($debugMode) {
                    $payload['upstream_status'] = $status;
                }

                return response()->json($payload, Response::HTTP_BAD_GATEWAY);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('CRLV: erro de conexão', [
                'message' => $e->getMessage(),
                'placa' => $placa,
                'renavam' => $renavam,
            ]);

            return response()->json(
                ['message' => 'Não foi possível conectar ao serviço do DETRAN. Tente novamente mais tarde.'],
                Response::HTTP_BAD_GATEWAY
            );
        } catch (\Throwable $e) {
            Log::error('CRLV: erro inesperado', [
                'message' => $e->getMessage(),
                'placa' => $placa,
                'renavam' => $renavam,
            ]);

            return response()->json(
                ['message' => 'Erro inesperado ao consultar a emissão/impressão do CRLV.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $htmlBody = $response->body();
        if (stripos($htmlBody, 'charset=iso-8859-1') !== false || stripos($htmlBody, 'charset=iso8859-1') !== false) {
            $htmlBody = @mb_convert_encoding($htmlBody, 'UTF-8', 'ISO-8859-1');
        }
        $warnings = [];
        if (preg_match_all('/errors\[errors\.length\]\s*=\s*["\']([^"\']+)["\'];/u', $htmlBody, $matches)) {
            foreach ($matches[1] as $msg) {
                $warnings[] = html_entity_decode($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        if (!empty($warnings)) {
            return response()->json(['message' => $warnings[0], 'detalhes' => $warnings], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $cookieTimestamp = now()->setTimezone('America/Sao_Paulo')->format('D M d Y H:i:s');
            $pdfResponse = Http::timeout(30)
                ->withHeaders($headers)
                ->withOptions(['verify' => false]) // REMOVER em produção
                ->withCookies([
                    'naoExibirPublic' => 'sim',
                    'dataUsuarPublic' => $cookieTimestamp . ' GMT-0300 (Horário Padrão de Brasília)',
                    'JSESSIONID'      => $token,
                ], $cookieDomain)
                ->get('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoCrlv.do', [
                    'method' => 'openPdf',
                ]);

            if (!$pdfResponse->successful()) {
                Log::warning('CRLV: falha ao acessar PDF', [
                    'status' => $pdfResponse->status(),
                    'placa' => $placa,
                    'renavam' => $renavam,
                    'body_preview' => substr($pdfResponse->body(), 0, 800),
                ]);

                return response()->json(
                    ['message' => 'Falha ao acessar o PDF do CRLV.'],
                    Response::HTTP_BAD_GATEWAY
                );
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('CRLV: erro de conexão ao acessar PDF', [
                'message' => $e->getMessage(),
                'placa' => $placa,
                'renavam' => $renavam,
            ]);

            return response()->json(
                ['message' => 'Não foi possível conectar ao serviço do DETRAN para baixar o PDF.'],
                Response::HTTP_BAD_GATEWAY
            );
        } catch (\Throwable $e) {
            Log::error('CRLV: erro inesperado ao acessar PDF', [
                'message' => $e->getMessage(),
                'placa' => $placa,
                'renavam' => $renavam,
            ]);

            return response()->json(
                ['message' => 'Erro inesperado ao baixar o PDF do CRLV.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $contentType = $pdfResponse->header('Content-Type', '');
        if (stripos($contentType, 'pdf') === false) {
            return response()->json(
                ['message' => 'Resposta inesperada ao tentar gerar o PDF do CRLV.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $arquivo = sprintf(
            'CRLV-%s-%s.pdf',
            preg_replace('/[^A-Z0-9]/', '', strtoupper($form['placa'])),
            now('America/Sao_Paulo')->format('YmdHis')
        );

        return response($pdfResponse->body(), Response::HTTP_OK, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$arquivo.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
