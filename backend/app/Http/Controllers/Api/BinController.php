<?php

namespace App\Http\Controllers\Api;

use App\Support\BinHtmlParser;
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
        $placa = strtoupper(trim((string) $request->query('placa', '')));
        $renavam = trim((string) $request->query('renavam', ''));
        $chassi = strtoupper(trim((string) $request->query('chassi', '')));
        $captcha = strtoupper(trim((string) $request->query('captcha', '')));
        $option = trim((string) $request->query('opcao', ''));

        $hasPlateRenavam = $placa !== '' && $renavam !== '';
        $hasChassi = $chassi !== '';

        if ($option === '') {
            $option = $hasChassi ? '1' : '2';
        }

        if (!in_array($option, ['1', '2'], true)) {
            return response()->json(
                ['message' => 'Opção de pesquisa inválida.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($captcha === '') {
            return response()->json(
                ['message' => 'Informe o captcha para realizar a consulta.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($option === '1' && !$hasChassi) {
            return response()->json(
                ['message' => 'Informe o chassi e o captcha para realizar a consulta.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($option === '2' && !$hasPlateRenavam) {
            return response()->json(
                ['message' => 'Informe placa e renavam e o captcha para realizar a consulta.'],
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
                'opcao' => $option,
                'valor' => $option === '1' ? $chassi : ($renavam !== '' ? $renavam : $placa),
                'placa' => $option === '2' ? $placa : '',
                'renavam' => $option === '2' ? $renavam : '',
                'chassi' => $option === '1' ? $chassi : '',
                'captchaResponse' => $captcha,
            ]);

        if (!$response->successful()) {
            return response()->json(
                ['message' => 'Falha ao consultar a base BIN externa.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $body = $response->body();

        $errors = $this->extractErrors($body);
        if (!empty($errors)) {
            return response()->json(
                [
                    'message' => $errors[0],
                    'details' => $errors,
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $parsed = BinHtmlParser::parse($body);

        if (!$this->hasUsefulData($parsed)) {
            return response()->json(
                ['message' => 'Nenhuma informação encontrada para os dados informados.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return response()->json(
            $parsed,
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
        if (preg_match_all('/errors\[errors\.length\]\s*=\s*[\'"]([^\'"]+)[\'"]\s*;?/iu', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $errors[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $errors;
    }

    private function hasUsefulData(array $payload): bool
    {
        $ignoreKeys = ['titulo', 'title', 'slug', 'label', 'gerado_em'];
        $found = false;

        array_walk_recursive($payload, static function ($value, $key) use (&$found, $ignoreKeys) {
            if ($found) {
                return;
            }

            if (in_array((string) $key, $ignoreKeys, true)) {
                return;
            }

            if (is_string($value) && trim($value) !== '') {
                $found = true;

                return;
            }

            if (is_numeric($value)) {
                $found = true;
            }
        });

        return $found;
    }
}
