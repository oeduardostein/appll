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

class ImpressaoCrlvController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // ---- Parser como CLOSURE (evita "Cannot redeclare") ----
        $parse = function (string $html): string {
            if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
                $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
            }

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new DOMXPath($dom);

            $norm = fn($s) => preg_replace('/\s+/u', ' ', trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $getText = function(?DOMNode $n) use ($norm) { return $n ? $norm($n->textContent ?? '') : null; };

            $findValueByLabel = function(string $label) use ($xp, $getText) {
                $q1 = sprintf('//span[contains(@class,"texto_black2")][normalize-space()="%s"]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q1)->item(0); if ($n) return $getText($n);

                $q2 = sprintf('//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q2)->item(0); if ($n) return $getText($n);

                $q3 = sprintf('//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td//span[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q3)->item(0); if ($n) return $getText($n);

                return null;
            };

            $findInlineValue = function(string $label) use ($xp, $getText) {
                $q = sprintf('//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/following::span[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q)->item(0);
                return $n ? $getText($n) : null;
            };

            $bodyText = $xp->query('//body')->item(0);
            $allText  = $getText($bodyText);
            $dataHora = null;
            if ($allText && preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $allText, $m)) {
                $dataHora = $m[1];
            }

            $L = [
                'Placa'         => ['Placa'],
                'Municipio'     => ['Município', 'Municipio'],
                'Renavam'       => ['Renavam'],
                'Chassi'        => ['Chassi'],
                'Tipo'          => ['Tipo'],
                'Procedencia'   => ['Procedência', 'Procedencia'],
                'Combustivel'   => ['Combustível', 'Combustivel'],
                'Cor'           => ['Cor'],
                'Marca'         => ['Marca'],
                'Categoria'     => ['Categoria'],
                'AnoFab'        => ['Ano Fabr.', 'Ano Fabr', "Ano\nFabr."],
                'AnoModelo'     => ['Ano Modelo', "Ano\nModelo"],
                'Proprietario'  => ['Nome do Proprietário', 'Nome do Proprietario'],

                'CRV_Exercicio' => ['Exerc. Licenciamento', 'Exercício Licenciamento', 'Exerc Licenciamento'],
                'CRV_DataLic'   => ['Licenciamento'],

                'SituacaoLicenciamento' => ['Licenciamento', 'Situação Licenciamento', 'Situacao Licenciamento'],
                'DebitosPendentes'      => ['Débito / Multas', 'Débitos', 'Debitos', 'Multas'],
            ];

            $pick = function(array $labels) use ($findValueByLabel, $findInlineValue) {
                foreach ($labels as $lab) {
                    $v = $findValueByLabel($lab); if ($v !== null && $v !== '') return $v;
                    $v = $findInlineValue($lab);  if ($v !== null && $v !== '') return $v;
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

            $lower = mb_strtolower($allText ?? '', 'UTF-8');
            $status = null;
            if (strpos($lower, 'licenciamento') !== false) {
                $status = $pick($L['SituacaoLicenciamento']) ?? null;
            }
            if (!$status && (strpos($lower, 'débitos') !== false || strpos($lower, 'debitos') !== false || strpos($lower, 'multas') !== false)) {
                $status = $pick($L['DebitosPendentes']) ?? null;
            }
            if ($status) $out['status'] = $status;

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
        };
        // ---- fim parser ----

        // Entradas
        $placa         = $request->query('placa');
        $renavam       = $request->query('renavam');
        $cpf           = $request->query('cpf', '');
        $cnpj          = $request->query('cnpj', '');
        $captcha       = $request->query('captchaResponse');
        $opcaoPesquisa = $request->query('opcaoPesquisa'); // 1 = CPF, 2 = CNPJ (ajuste se diferente)

        if (!$placa || !$renavam || !$captcha || !$opcaoPesquisa) {
            return response()->json(
                ['message' => 'Informe placa, renavam, captchaResponse e opcaoPesquisa.'],
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
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
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
            'placa'           => strtoupper($placa),
            'renavam'         => $renavam,
            'opcaoPesquisa'   => (string) $opcaoPesquisa,
            'captchaResponse' => strtoupper($captcha),
        ];
        if ((string)$opcaoPesquisa === '1') {
            $form['cpf'] = preg_replace('/\D+/', '', (string)$cpf);
        } elseif ((string)$opcaoPesquisa === '2') {
            $form['cnpj'] = preg_replace('/\D+/', '', (string)$cnpj);
        }
        // se a tela aceitar ambos sempre, pode remover esse condicional

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false]) // REMOVER em produção
            ->withCookies([
                'naoExibirPublic' => 'sim',
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID'      => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/impressaoCrlv.do', $form);

        // (Opcional) tratar erro HTTP:
        // if (!$response->successful()) {
        //     return response()->json(['message' => 'Falha ao consultar a emissão/impressão do CRLV.'], Response::HTTP_BAD_GATEWAY);
        // }

        $body = $parse($response->body());

        return response($body, Response::HTTP_OK)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }
}
