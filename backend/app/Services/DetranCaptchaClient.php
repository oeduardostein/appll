<?php

namespace App\Services;

use App\Exceptions\CaptchaException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class DetranCaptchaClient
{
    /**
     * Fetch captcha image bytes and related metadata from the external DETRAN endpoint.
     *
     * @return array{body: string, content_type: string}
     *
     * @throws CaptchaException
     */
    public function fetch(): array
    {
        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (!$token) {
            throw new CaptchaException(
                'Nenhum token encontrado para gerar o captcha.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $captchaUrl = 'https://www.e-crvsp.sp.gov.br/gever/jcaptcha?id=' . time();

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Cookie' => 'dataUsuarPublic=Thu%20Aug%2001%202024%2014%3A46%3A49%20GMT-0300%20(Hor%C3%A1rio%20Padr%C3%A3o%20de%20Bras%C3%ADlia); naoExibirPublic=sim; JSESSIONID=' . $token,
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
        ];

        $response = Http::withHeaders($headers)->withOptions([
            'verify' => false,
        ])->get($captchaUrl);

        if (!$response->successful()) {
            throw new CaptchaException(
                'Não foi possível obter o captcha da base externa.',
                Response::HTTP_BAD_GATEWAY
            );
        }

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type', 'image/jpeg'),
        ];
    }
}
