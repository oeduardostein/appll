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
        // Parser como CLOSURE para evitar redeclaração
        $parse = function (string $html): string {
            // Força encoding amigável ao DOM (página declara ISO-8859-1)
            if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
                $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
            }

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new DOMXPath($dom);

            // Helpers
            $norm = fn($s) => preg_replace('/\s+/u', ' ', trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $getText = function (?DOMNode $n) use ($norm) { return $n ? $norm($n->textContent ?? '') : null; };

            // Label → valor (à direita)
            $findValueByLabel = function (string $label) use ($xp, $getText) {
                $q1 = sprintf('//span[contains(@class,"texto_black2")][normalize-space()="%s"]/ancestor::td[1]/following-sibling::td[1]//*[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q1)->item(0); if ($n) return $getText($n);

                $q2 = sprintf('//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td[1]//*[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q2)->item(0); if ($n) return $getText($n);

                $q3 = sprintf('//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td//*[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q3)->item(0); if ($n) return $getText($n);

                return null;
            };

            // Inline (fallback)
            $findInlineValue = function (string $label) use ($xp, $getText) {
                $q = sprintf('//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/following::span[contains(@class,"texto_menor")][1]', $label);
                $n = $xp->query($q)->item(0);
                return $n ? $getText($n) : null;
            };

            // Data/hora impressa (ex.: 03/11/2025 16:19:19)
            $bodyText = $xp->query('//body')->item(0);
            $allText  = $getText($bodyText);
            $dataHora = null;
            if ($allText && preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $allText, $m)) {
                $dataHora = $m[1];
            }

            // Labels da tela RENAINF (exatamente como no HTML enviado)
            $L = [
                // bloco "Dados da Consulta"
                'Placa'                 => ['Placa'],
                'UF_Emplacamento'       => ['UF de Emplacamento'],
                'IndicadorExigibilidade'=> ['Indicador Exigibilidade'],

                // bloco "Quantidade de Ocorrências"
                'QtdOcorrencias'        => ['Quantidade de Ocorrências'],

                // (genéricos – mantidos se a tela tiver)
                'Municipio'    => ['Município', 'Municipio'],
                'Renavam'      => ['Renavam'],
                'Chassi'       => ['Chassi'],
                'Tipo'         => ['Tipo'],
                'Procedencia'  => ['Procedência', 'Procedencia'],
                'Combustivel'  => ['Combustível', 'Combustivel'],
                'Cor'          => ['Cor'],
                'Marca'        => ['Marca'],
                'Categoria'    => ['Categoria'],
                'AnoFab'       => ['Ano Fabr.', 'Ano Fabr', "Ano\nFabr."],
                'AnoModelo'    => ['Ano Modelo', "Ano\nModelo"],
                'Proprietario' => ['Nome do Proprietário', 'Nome do Proprietario'],
            ];

            $pick = function (array $labels) use ($findValueByLabel, $findInlineValue) {
                foreach ($labels as $lab) {
                    $v = $findValueByLabel($lab); if ($v !== null && $v !== '') return $v;
                    $v = $findInlineValue($lab);  if ($v !== null && $v !== '') return $v;
                }
                return null;
            };

            // Extrai a tabela de ocorrências (id="listRenainfId")
            $ocorrencias = [];
            $table = $xp->query('//*[@id="listRenainfId"]/tbody')->item(0);
            if ($table) {
                foreach ($xp->query('./tr', $table) as $tr) {
                    $tds = $xp->query('./td', $tr);
                    if ($tds->length >= 6) {
                        // Colunas (ignorando a 1ª que tem o botão)
                        $orgao         = trim($getText($tds->item(1)));
                        $autoInfracao  = trim($getText($tds->item(2)));
                        $infracao      = trim($getText($tds->item(3)));
                        $dataInfracao  = trim($getText($tds->item(4)));
                        $exigibilidade = trim($getText($tds->item(5)));

                        $ocorrencias[] = [
                            'orgao_autuador'    => $orgao,         // ex.: "271070"
                            'auto_infracao'     => $autoInfracao,  // ex.: "7RA1254609"
                            'infracao'          => $infracao,      // ex.: "5746"
                            'data_infracao'     => $dataInfracao,  // ex.: "08/05/2025"
                            'exigibilidade'     => $exigibilidade, // ex.: "Não"
                        ];
                    }
                }
            }

            // Monta JSON padronizado
            $out = [
                'fonte' => [
                    'titulo'    => 'eCRVsp - DETRAN - São Paulo',
                    'gerado_em' => $dataHora,
                ],
                'consulta' => [
                    'placa'                 => $pick($L['Placa']),                 // "FMY8A88"
                    'uf_emplacamento'       => $pick($L['UF_Emplacamento']),       // "SP"
                    'indicador_exigibilidade'=> $pick($L['IndicadorExigibilidade']) // "Todas as Multas"
                ],
                'renainf' => [
                    'quantidade_ocorrencias' => $pick($L['QtdOcorrencias']),       // "28"
                    'ocorrencias'            => $ocorrencias,                      // lista com as linhas da tabela
                ],
            ];

            // Sanitização suave
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

        // ---------- Entrada ----------
        $indExigib  = $request->query('indExigib');      // "Todas", "Exigíveis", etc. (como no seu front)
        $periodoIni = $request->query('periodoIni');     // dd/mm/aaaa
        $periodoFin = $request->query('periodoFin');     // dd/mm/aaaa
        $placa      = $request->query('placa');
        $uf         = $request->query('uf');
        $captcha    = $request->query('captchaResponse');

        if (!$placa || !$uf || !$periodoIni || !$periodoFin || $indExigib === null || !$captcha) {
            return response()->json(
                ['message' => 'Informe placa, uf, periodoIni, periodoFin, indExigib e captchaResponse.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // ---------- Token via admin_settings.value ----------
        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a consulta.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // ---------- Requisição ----------
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

        $form = [
            'method'          => 'pesquisar',
            'placa'           => strtoupper($placa),
            'uf'              => strtoupper($uf),
            'periodoIni'      => $periodoIni,
            'periodoFin'      => $periodoFin,
            'indExigib'       => (string) $indExigib,
            'captchaResponse' => strtoupper($captcha),
        ];

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false]) // em produção, prefira verify=true
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID'      => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/renainf/placa.do', $form);

        // Saída JSON (tela HTML → JSON)
        $body = $parse($response->body());

        return response($body, Response::HTTP_OK)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }
}
