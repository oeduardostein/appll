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

class RenainfPlacaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // -------- Parser (closure p/ evitar redeclaração) --------
        $parse = function (string $html): string {
            // Página declara ISO-8859-1 — converte p/ entidades HTML (seguro p/ DOM)
            if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
                $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
            }

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new DOMXPath($dom);

            $norm = fn($s) => preg_replace('/\s+/u', ' ', trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $txt  = function (?DOMNode $n) use ($norm) { return $n ? $norm($n->textContent ?? '') : null; };

            // Busca valor ao lado de um label (<span.texto_black2>)
            $findValueByLabel = function (string $label) use ($xp, $txt) {
                // match exato
                $q1 = sprintf(
                    '//span[contains(@class,"texto_black2")][normalize-space()="%s"]/ancestor::td[1]/following-sibling::td[1]//*[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n1 = $xp->query($q1)->item(0);
                if ($n1) return $txt($n1);
                // contém (tolerante)
                $q2 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td[1]//*[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n2 = $xp->query($q2)->item(0);
                if ($n2) return $txt($n2);
                // fallback (colspan)
                $q3 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td//*[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n3 = $xp->query($q3)->item(0);
                return $n3 ? $txt($n3) : null;
            };

            // Timestamp (ex.: 03/11/2025 16:45:48)
            $allText  = $txt($xp->query('//body')->item(0));
            $dataHora = null;
            if ($allText && preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $allText, $m)) {
                $dataHora = $m[1];
            }

            // Helper: percorre fieldset e devolve mapa label => valor
            $extractFieldsetData = function (callable $legendMatcher) use ($xp, $txt) {
                foreach ($xp->query('//fieldset') as $fieldset) {
                    $legendNode = $xp->query('./legend', $fieldset)->item(0);
                    $legendText = $txt($legendNode);
                    if (! $legendMatcher($legendText)) {
                        continue;
                    }

                    $data = [];
                    $tds = $xp->query('.//td', $fieldset);
                    $count = $tds->length;
                    for ($i = 0; $i + 1 < $count; $i += 2) {
                        $label = $txt($tds->item($i));
                        $value = $txt($tds->item($i + 1));
                        if ($label !== null && $label !== '') {
                            $data[$label] = $value;
                        }
                    }
                    return $data;
                }

                return [];
            };

            $consultaInfo = $extractFieldsetData(
                fn (?string $legend) => $legend !== null && stripos($legend, 'Dados da Consulta') !== false
            );

            $quantidadeInfo = $extractFieldsetData(
                fn (?string $legend) => $legend !== null && stripos($legend, 'Quantidade de Ocorr') !== false
            );

            // Tabela de ocorrências (#listRenainfId) — robusto a mudanças de <tbody>
            $ocorrencias = [];
            foreach ($xp->query('//table[@id="listRenainfId"]//tr[td]') as $tr) {
                $tds = $xp->query('./td', $tr);
                if ($tds->length >= 6) {
                    // ignora col 0 (botão lupa)
                    $orgao         = $txt($tds->item(1));
                    $autoInfracao  = $txt($tds->item(2));
                    $infracao      = $txt($tds->item(3));
                    $dataInfracao  = $txt($tds->item(4));
                    $exigibilidade = $txt($tds->item(5));

                    // só adiciona linhas reais (defesa contra header duplicado etc.)
                    $normalizedOrgao = $orgao !== null ? mb_strtolower($orgao, 'UTF-8') : '';
                    if ($normalizedOrgao !== '' && !str_contains($normalizedOrgao, 'orgão autuador')) {
                        $ocorrencias[] = [
                            'orgao_autuador' => $orgao,          // ex.: 264750
                            'auto_infracao'  => $autoInfracao,   // ex.: D500042316
                            'infracao'       => $infracao,       // ex.: 7455
                            'data_infracao'  => $dataInfracao,   // ex.: 22/04/2025
                            'exigibilidade'  => $exigibilidade,  // ex.: Sim/Não
                        ];
                    }
                }
            }

            $consultaPlaca  = $consultaInfo['Placa'] ?? $findValueByLabel('Placa');
            $consultaUf     = $consultaInfo['UF de Emplacamento'] ?? $findValueByLabel('UF de Emplacamento');
            $consultaExig   = $consultaInfo['Indicador Exigibilidade'] ?? $findValueByLabel('Indicador Exigibilidade');

            $quantidadeOcorrencias = null;
            foreach ($quantidadeInfo as $label => $value) {
                if (stripos($label, 'Quantidade') !== false) {
                    $quantidadeOcorrencias = $value;
                    break;
                }
            }
            if ($quantidadeOcorrencias === null) {
                $quantidadeOcorrencias = $findValueByLabel('Quantidade de Ocorrências');
            }
            if ($quantidadeOcorrencias === null && !empty($ocorrencias)) {
                $quantidadeOcorrencias = (string) count($ocorrencias);
            }

            $fonteTitulo = 'eCRVsp - DETRAN - São Paulo';
            $titleNode = $xp->query('//head/title')->item(0);
            if ($titleNode) {
                $maybeTitle = $txt($titleNode);
                if (!empty($maybeTitle)) {
                    $fonteTitulo = $maybeTitle;
                }
            }

            // JSON de saída
            $out = [
                'fonte' => [
                    'titulo'    => $fonteTitulo,
                    'gerado_em' => $dataHora,
                ],
                'consulta' => [
                    'placa'                   => $consultaPlaca,
                    'uf_emplacamento'         => $consultaUf,
                    'indicador_exigibilidade' => $consultaExig,
                ],
                'renainf' => [
                    'quantidade_ocorrencias'  => $quantidadeOcorrencias,
                    'ocorrencias'             => $ocorrencias,
                ],
            ];

            // Sanitização leve (apenas espaços)
            $sanitize = function (&$arr) use (&$sanitize) {
                foreach ($arr as $k => &$v) {
                    if (is_array($v)) { $sanitize($v); continue; }
                    if ($v === null) continue;
                    $v = preg_replace('/\s+/u', ' ', trim($v));
                }
            };
            $sanitize($out);

            return json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        };
        // -------- Fim parser --------

        // -------- Entrada --------
        $indExigib  = $request->query('indExigib');     // "1" (Cobrança) | "2" (Todas)
        $periodoIni = $request->query('periodoIni');    // dd/mm/aaaa
        $periodoFin = $request->query('periodoFin');    // dd/mm/aaaa
        $placa      = $request->query('placa');         // obrigatório
        $uf         = $request->query('uf');            // OPCIONAL na tela
        // aceitar captchaResponse OU captcha
        $captcha    = $request->query('captchaResponse') ?: $request->query('captcha');

        // Validação (UF não é obrigatório na tela)
        if (!$placa || !$periodoIni || !$periodoFin || $indExigib === null || !$captcha) {
            return response()->json(
                ['message' => 'Informe placa, periodoIni, periodoFin, indExigib e captchaResponse (ou captcha).'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // -------- Token (JSESSIONID) --------
        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a consulta.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // -------- Requisição --------
        $headers = [
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language'           => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control'             => 'max-age=0',
            'Connection'                => 'keep-alive',
            'Content-Type'              => 'application/x-www-form-urlencoded',
            'Origin'                    => 'https://www.e-crvsp.sp.gov.br',
            'Referer'                   => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/renainf/placa.do',
            'Sec-Fetch-Dest'            => 'frame',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => 'same-origin',
            'Sec-Fetch-User'            => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'sec-ch-ua'                 => '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile'          => '?0',
            'sec-ch-ua-platform'        => '"Windows"',
        ];
        $cookieDomain = 'www.e-crvsp.sp.gov.br';

        // Monta o formulário exatamente como a tela usa (method=pesquisar)
        $form = [
            'method'          => 'pesquisar',
            'placa'           => strtoupper($placa),
            'periodoIni'      => $periodoIni,
            'periodoFin'      => $periodoFin,
            'indExigib'       => (string) $indExigib,
            'captchaResponse' => strtoupper($captcha),
        ];
        // Só envia UF se o cliente tiver informado (é opcional)
        if (!empty($uf)) {
            $form['uf'] = strtoupper($uf);
        }

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false]) // em produção, prefira verify=true
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID'      => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/renainf/placa.do', $form);

        // -------- Saída JSON --------
        $body = $parse($response->body());

        return response($body, Response::HTTP_OK)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }
}
