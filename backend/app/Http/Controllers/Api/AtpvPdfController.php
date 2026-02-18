<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\FindsUserFromApiToken;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AtpvPdfController extends Controller
{
    use FindsUserFromApiToken;

    public function __invoke(Request $request): Response
    {
        $user = $this->findUserFromRequest($request);
        $traceId = (string) Str::uuid();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $data = $request->validate([
            'placa' => ['required', 'string', 'max:10'],
            'renavam' => ['required', 'string', 'max:20'],
            'captcha' => ['required', 'string', 'max:12'],
        ]);

        Log::info('ATPV PDF: inicio', [
            'trace_id' => $traceId,
            'user_id' => $user->id ?? null,
            'fields' => [
                'placa' => strtoupper(trim($data['placa'])),
                'renavam' => $this->maskKeepLast($this->onlyDigits($data['renavam']), 4),
                'captcha' => $this->maskCaptcha($data['captcha']),
            ],
        ]);

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (! $token) {
            Log::error('ATPV PDF: token ausente', [
                'trace_id' => $traceId,
                'user_id' => $user->id ?? null,
            ]);
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a impressão.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $headers = [
            'Accept' => 'application/pdf,text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoAtpv.do',
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
        $cookieJar = CookieJar::fromArray([
            'naoExibirPublic' => 'sim',
            'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
            'JSESSIONID' => $token,
        ], $cookieDomain);

        $form = [
            'method' => 'pesquisarSEV',
            'placa' => strtoupper($data['placa']),
            'renavam' => $data['renavam'],
            'captchaResponse' => strtoupper($data['captcha']),
        ];

        $initialResponse = Http::withHeaders($headers)
            ->withOptions([
                'verify' => false,
                'cookies' => $cookieJar,
                'allow_redirects' => true,
            ])
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoAtpv.do', $form);

        $initialBody = $initialResponse->body();
        $errors = $this->extractErrors($initialBody);
        $initialStats = $initialResponse->handlerStats();
        $initialUrl = is_array($initialStats) ? ($initialStats['url'] ?? null) : null;

        Log::info('ATPV PDF: retorno pesquisarSEV', [
            'trace_id' => $traceId,
            'status' => $initialResponse->status(),
            'content_type' => $initialResponse->header('Content-Type'),
            'effective_url' => $initialUrl,
            'errors' => $errors,
            'preview' => $this->buildHtmlPreview($initialBody),
        ]);

        if (! empty($errors)) {
            return response()->json(
                ['message' => $errors[0], 'detalhes' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $pdfResponse = Http::withHeaders($headers)
            ->withOptions([
                'verify' => false,
                'cookies' => $cookieJar,
                'allow_redirects' => true,
            ])
            ->get('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoAtpv.do', [
                'method' => 'openPdf',
            ]);

        $pdfStats = $pdfResponse->handlerStats();
        $pdfUrl = is_array($pdfStats) ? ($pdfStats['url'] ?? null) : null;
        Log::info('ATPV PDF: retorno openPdf', [
            'trace_id' => $traceId,
            'status' => $pdfResponse->status(),
            'content_type' => $pdfResponse->header('Content-Type'),
            'effective_url' => $pdfUrl,
            'body_len' => strlen($pdfResponse->body()),
            'preview' => $this->buildHtmlPreview($pdfResponse->body()),
        ]);

        if (! $pdfResponse->successful()) {
            $pdfErrors = $this->extractErrors($pdfResponse->body());
            Log::warning('ATPV PDF: falha openPdf', [
                'trace_id' => $traceId,
                'status' => $pdfResponse->status(),
                'errors' => $pdfErrors,
                'preview' => $this->buildHtmlPreview($pdfResponse->body()),
            ]);
            return response()->json(
                [
                    'message' => $pdfErrors[0] ?? 'Falha ao acessar o PDF da ATPV-e.',
                    'detalhes' => $pdfErrors,
                ],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $contentType = $pdfResponse->header('Content-Type', '');
        if (stripos((string) $contentType, 'pdf') === false) {
            $pdfErrors = $this->extractErrors($pdfResponse->body());
            Log::warning('ATPV PDF: content-type inesperado', [
                'trace_id' => $traceId,
                'content_type' => $contentType,
                'errors' => $pdfErrors,
                'preview' => $this->buildHtmlPreview($pdfResponse->body()),
            ]);
            return response()->json(
                [
                    'message' => $pdfErrors[0] ?? 'Resposta inesperada ao tentar gerar o PDF da ATPV-e.',
                    'detalhes' => $pdfErrors,
                ],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $filename = sprintf(
            'ATPV-%s-%s.pdf',
            preg_replace('/[^A-Z0-9]/', '', strtoupper($form['placa'])),
            now('America/Sao_Paulo')->format('YmdHis')
        );

        Log::info('ATPV PDF: sucesso', [
            'trace_id' => $traceId,
            'filename' => $filename,
            'bytes' => strlen($pdfResponse->body()),
            'content_type' => $contentType,
        ]);

        return response($pdfResponse->body(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function unauthorizedResponse(): Response
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Não autenticado.',
        ], 401);
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

    private function buildHtmlPreview(string $body): string
    {
        if ($body === '') {
            return '';
        }

        $normalized = $body;
        if (stripos($normalized, 'charset=iso-8859-1') !== false || stripos($normalized, 'charset=iso8859-1') !== false) {
            $normalized = @mb_convert_encoding($normalized, 'UTF-8', 'ISO-8859-1');
        }

        $text = strip_tags($normalized);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        return mb_substr($text, 0, 280);
    }

    private function onlyDigits(?string $value): string
    {
        return $value ? preg_replace('/\D/', '', $value) ?? '' : '';
    }

    private function maskCaptcha(string $captcha): string
    {
        $captcha = trim($captcha);
        if ($captcha === '') {
            return '';
        }

        return str_repeat('*', max(0, strlen($captcha) - 2)) . substr($captcha, -2);
    }

    private function maskKeepLast(string $value, int $keepLast = 4): string
    {
        if ($value === '') {
            return '';
        }

        if (strlen($value) <= $keepLast) {
            return $value;
        }

        return str_repeat('*', strlen($value) - $keepLast) . substr($value, -$keepLast);
    }
}
