<?php

namespace App\Http\Controllers\Api;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ImpressaoAtpvController extends Controller
{
    /**
     * Consulta dados para emissão/impressão da ATPV no e-CRVsp e retorna JSON padronizado.
     * Ex.: GET /api/impressao-atpv?placa=ABC1D23&renavam=1234567890&captchaResponse=AB12
     */
    public function __invoke(Request $request): Response
    {
        // -------- Parser reaproveitado (mesma base dos demais endpoints) --------
        function parseDetranHtmlToJson(string $html): string {
            if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
                $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
            }

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new DOMXPath($dom);

            $norm = fn($s) => preg_replace('/\s+/u', ' ', trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $getText = function(?DOMNode $n) use ($norm) {
                if (!$n) return null;
                return $norm($n->textContent ?? '');
            };

            $findValueByLabel = function(string $label) use ($xp, $getText) {
                $q1 = sprintf(
                    '//span[contains(@class,"texto_black2")][normalize-space()="%s"]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q1)->item(0);
                if ($n) return $getText($n);

                $q2 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q2)->item(0);
                if ($n) return $getText($n);

                $q3 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q3)->item(0);
                if ($n) return $getText($n);

                return null;
            };

            $findInlineValue = function(string $label) use ($xp, $getText) {
                $q = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/following::span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q)->item(0);
                return $n ? $getText($n) : null;
            };

            $bodyText = $xp->query('//body')->item(0);
            $allText = $getText($bodyText);
            $dataHora = null;
            if ($allText && preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $allText, $m)) {
                $dataHora = $m[1];
            }

            // Mapa de labels genéricos + alguns comuns à ATPV (se existirem na tela)
            $L = [
                'Placa'                     => ['Placa'],
                'Municipio'                 => ['Município', 'Municipio'],
                'Renavam'                   => ['Renavam'],
                'Chassi'                    => ['Chassi'],
                'Tipo'                      => ['Tipo'],
                'Procedencia'               => ['Procedência', 'Procedencia'],
                'Combustivel'               => ['Combustível', 'Combustivel'],
                'Cor'                       => ['Cor'],
                'Marca'                     => ['Marca'],
                'Categoria'                 => ['Categoria'],
                'AnoFab'                    => ['Ano Fabr.', 'Ano Fabr', "Ano\nFabr."],
                'AnoModelo'                 => ['Ano Modelo', "Ano\nModelo"],
                'Proprietario'              => ['Nome do Proprietário', 'Nome do Proprietario'],

                // Campos frequentemente úteis para ATPV
                'CRV_Exercicio'             => ['Exerc. Licenciamento', 'Exercício Licenciamento', 'Exerc Licenciamento'],
                'CRV_DataLic'               => ['Licenciamento'],
                'ATPV_Status'               => ['ATPV', 'Autorização para Transferência', 'Autorizacao para Transferencia'],
            ];

            $pick = function(array $labels) use ($findValueByLabel, $findInlineValue) {
                foreach ($labels as $lab) {
                    $v = $findValueByLabel($lab);
                    if ($v !== null && $v !== '') return $v;
                    $v = $findInlineValue($lab);
                    if ($v !== null && $v !== '') return $v;
                }
                return null;
            };

            $out = [
                'fonte' => [
                    'titulo'    => 'eCRVsp - DETRAN - São Paulo',
                    'gerado_em' => $dataHora,
                ],
                'veiculo' => [
                    'placa'          => $pick($L['Placa']),
                    'municipio'      => $pick($L['Municipio']),
                    'renavam'        => $pick($L['Renavam']),
                    'chassi'         => $pick($L['Chassi']),
                    'tipo'           => $pick($L['Tipo']),
                    'procedencia'    => $pick($L['Procedencia']),
                    'combustivel'    => $pick($L['Combustivel']),
                    'cor'            => $pick($L['Cor']),
                    'marca'          => $pick($L['Marca']),
                    'categoria'      => $pick($L['Categoria']),
                    'ano_fabricacao' => $pick($L['AnoFab']),
                    'ano_modelo'     => $pick($L['AnoModelo']),
                ],
                'proprietario' => [
                    'nome' => $pick($L['Proprietario']),
                ],
                'crv_crlv_atualizacao' => [
                    'exercicio_licenciamento' => $pick($L['CRV_Exercicio']),
                    'data_licenciamento'      => $pick($L['CRV_DataLic']),
                ],
            ];

            // Se houver alguma indicação textual de ATPV disponível/bloqueada:
            $lower = mb_strtolower($allText ?? '', 'UTF-8');
            if (strpos($lower, 'atpv') !== false || strpos($lower, 'transfer') !== false) {
                $out['atpv_status'] = $pick($L['ATPV_Status']) ?? 'Verificar detalhes na página';
            }

            // Sanitização
            $sanitize = function (&$arr) use (&$sanitize) {
                foreach ($arr as $k => &$v) {
                    if (is_array($v)) { $sanitize($v); continue; }
                    if ($v === null) continue;
                    $v = preg_replace('/\s+/u', ' ', trim($v));
                    if (preg_match('/^n\s*a\s*d\s*a\s*consta$/iu', preg_replace('/\s+/', '', $v))) {
                        $v = 'Nada Consta';
                    }
                }
            };
            $sanitize($out);

            return json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        // ---------------------------------------------------------------------

        // Entrada
        $placa     = $request->query('placa');
        $renavam   = $request->query('renavam');
        $captcha   = $request->query('captchaResponse');

        if (!$placa || !$renavam || !$captcha) {
            return response()->json(
                ['message' => 'Informe placa, renavam e captchaResponse.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Token em admin_settings.value
        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a consulta.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Chamada ao endpoint de Impressão de ATPV
        $headers = [
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language'           => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control'             => 'max-age=0',
            'Connection'                => 'keep-alive',
            'Content-Type'              => 'application/x-www-form-urlencoded',
            'Origin'                    => 'https://www.e-crvsp.sp.gov.br',
            'Referer'                   => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoAtpv.do',
            'Sec-Fetch-Dest'            => 'frame',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => 'same-origin',
            'Sec-Fetch-User'            => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent'                => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1',
            'sec-ch-ua'                 => '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile'          => '?0',
            'sec-ch-ua-platform'        => '"iOS"',
        ];
        $cookieDomain = 'www.e-crvsp.sp.gov.br';

        $form = [
            'method'          => 'pesquisarSEV',
            'placa'           => strtoupper($placa),
            'renavam'         => $renavam,
            'captchaResponse' => strtoupper($captcha),
        ];

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false]) // em produção, ideal manter verificação TLS
            ->withCookies([
                'naoExibirPublic' => 'sim',
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID'      => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoAtpv.do', $form);

        // Parse do HTML → JSON padronizado
        $body = parseDetranHtmlToJson($response->body());

        return response($body, Response::HTTP_OK)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }
}
