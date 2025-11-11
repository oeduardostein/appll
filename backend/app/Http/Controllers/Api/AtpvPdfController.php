<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AtpvPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $this->findUserFromRequest($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $data = $request->validate([
            'placa' => ['required', 'string', 'max:10'],
            'renavam' => ['required', 'string', 'max:20'],
            'captcha' => ['required', 'string', 'max:12'],
        ]);

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (! $token) {
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

        $form = [
            'method' => 'pesquisarSEV',
            'placa' => strtoupper($data['placa']),
            'renavam' => $data['renavam'],
            'captchaResponse' => strtoupper($data['captcha']),
        ];

        $initialResponse = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'naoExibirPublic' => 'sim',
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoAtpv.do', $form);

        $initialBody = $initialResponse->body();
        $errors = $this->extractErrors($initialBody);
        if (! empty($errors)) {
            return response()->json(
                ['message' => $errors[0], 'detalhes' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $pdfResponse = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'naoExibirPublic' => 'sim',
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], $cookieDomain)
            ->get('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoAtpv.do', [
                'method' => 'openPdf',
            ]);

        if (! $pdfResponse->successful()) {
            return response()->json(
                ['message' => 'Falha ao acessar o PDF da ATPV-e.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $contentType = $pdfResponse->header('Content-Type', '');
        if (stripos((string) $contentType, 'pdf') === false) {
            return response()->json(
                ['message' => 'Resposta inesperada ao tentar gerar o PDF da ATPV-e.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $filename = sprintf(
            'ATPV-%s-%s.pdf',
            preg_replace('/[^A-Z0-9]/', '', strtoupper($form['placa'])),
            now('America/Sao_Paulo')->format('YmdHis')
        );

        return response($pdfResponse->body(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function findUserFromRequest(Request $request): ?User
    {
        $token = $this->extractTokenFromRequest($request);

        if (! $token) {
            return null;
        }

        return User::where('api_token', hash('sha256', $token))->first();
    }

    private function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');

        if (is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if ($token !== '') {
                return $token;
            }
        }

        $token = $request->input('token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        return null;
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
}
