<?php

namespace App\Http\Controllers\Api;

use App\Models\AtpvRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class RegistrarAssinaturaAtpvController extends BaseAtpvController
{
    public function __invoke(Request $request): Response
    {
        $user = $this->findUserFromRequest($request);

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        $data = $request->validate([
            'registro_id' => ['required', 'integer', 'exists:atpv_requests,id'],
            'assinatura_digital' => ['required', 'boolean'],
        ]);

        /** @var AtpvRequest $registro */
        $registro = AtpvRequest::query()
            ->where('id', $data['registro_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (! $token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para registrar a assinatura.'],
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
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/incluirIntencaoVenda.do',
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

        $form = [
            'method' => 'registrarTipoAssinatura',
            'assinaturaDigital' => $data['assinatura_digital'] ? 'true' : 'false',
        ];

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], 'www.e-crvsp.sp.gov.br')
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/incluirIntencaoVenda.do', $form);

        $body = $response->body();
        $errors = $this->extractErrors($body);

        if (! empty($errors)) {
            return response()->json(
                [
                    'message' => $errors[0],
                    'detalhes' => $errors,
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $messages = $this->extractMessages($body);

        $payload = $registro->response_payload ?? [];
        $payload['assinatura'] = [
            'messages' => $messages,
        ];

        $registro->update([
            'assinatura_digital' => $data['assinatura_digital'],
            'assinatura_registrada_em' => now(),
            'status' => 'completed',
            'response_payload' => $payload,
        ]);

        return response()->json(
            [
                'message' => $messages[0] ?? 'Assinatura registrada com sucesso.',
                'registro_id' => $registro->id,
                'assinatura_digital' => $data['assinatura_digital'],
            ],
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
