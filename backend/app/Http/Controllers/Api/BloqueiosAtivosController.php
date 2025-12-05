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

class BloqueiosAtivosController extends Controller
{
    /**
     * Consulta Bloqueios Ativos (RENAJUD) e retorna JSON com os campos visíveis no HTML.
     * Ex.: GET /api/bloqueios-ativos?opcaoPesquisa=1&chassi=9C2JC4810BR016583&captcha=AB12
     * Também aceita captchaResponse como alias de captcha.
     */
    public function __invoke(Request $request): Response
    {
        // ---------- Parser em closure (evita "Cannot redeclare") ----------
        $parse = function (string $html): string {
            // Normalizar encoding p/ DOM (página declara ISO-8859-1)
            if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
                $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
            }

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new DOMXPath($dom);

            $norm   = fn($s) => preg_replace('/\s+/u', ' ', trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $getTxt = function(?DOMNode $n) use ($norm) { return $n ? $norm($n->textContent ?? '') : null; };

            // Pega o <span class="texto_menor"> imediatamente à direita do label
            $findValueByLabel = function(string $label) use ($xp, $getTxt) {
                // label exato
                $q1 = sprintf(
                    '//span[contains(@class,"texto_black2")][normalize-space()="%s"]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q1)->item(0);
                if ($n) return $getTxt($n);
                // label contém (tolerante)
                $q2 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q2)->item(0);
                if ($n) return $getTxt($n);
                // fallback quando há colspan
                $q3 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q3)->item(0);
                return $n ? $getTxt($n) : null;
            };

            // Timestamp do rodapé (ex.: 03/11/2025 16:26:42)
            $bodyText = $xp->query('//body')->item(0);
            $allText  = $getTxt($bodyText);
            $dataHora = null;
            if ($allText && preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $allText, $m)) {
                $dataHora = $m[1];
            }

            // ===== Campos do HTML enviado =====
            // Bloco: "Dados de Consulta a Bloqueios RENAJUD"
            $placa = $findValueByLabel('Placa');

            // Permitir variações com/sem acento em "Município Placa"
            $municipioPlaca = $findValueByLabel('Município Placa')
                            ?? $findValueByLabel('Municipio Placa');

            $chassi = $findValueByLabel('Chassi');

            // Bloco: "Quantidade de Bloqueios"
            $qtdEncontradas = $findValueByLabel('Quantidade de Ocorrências Encontradas')
                            ?? $findValueByLabel('Quantidade de Ocorr\u00eancias Encontradas') // tolerância
                            ?? $findValueByLabel('Quantidade de Ocorrencias Encontradas');

            $qtdExibidas = $findValueByLabel('Quantidade de Ocorrências Exibidas')
                         ?? $findValueByLabel('Quantidade de Ocorr\u00eancias Exibidas')
                         ?? $findValueByLabel('Quantidade de Ocorrencias Exibidas');

            // Bloco: "Informações de Bloqueios RENAJUD"
            $dataInclusao  = $findValueByLabel('Data da Inclusão')
                           ?? $findValueByLabel('Data da Inclus\u00e3o')
                           ?? $findValueByLabel('Data da Inclusao')
                           ?? $findValueByLabel('Data de Inclusão do Bloqueio')
                           ?? $findValueByLabel('Data de Inclus\u00e3o do Bloqueio')
                           ?? $findValueByLabel('Data de Inclusao do Bloqueio');

            $horaInclusao  = $findValueByLabel('Hora da Inclusão')
                           ?? $findValueByLabel('Hora da Inclus\u00e3o')
                           ?? $findValueByLabel('Hora da Inclusao');

            $tipoRestricao = $findValueByLabel('Tipo de Restrição Judicial')
                           ?? $findValueByLabel('Tipo de Restri\u00e7\u00e3o Judicial')
                           ?? $findValueByLabel('Tipo de Restricao Judicial')
                           ?? $findValueByLabel('Tipo de Bloqueio')
                           ?? $findValueByLabel('Tipo do Bloqueio');

            $codTribunal   = $findValueByLabel('Código do Tribunal')
                           ?? $findValueByLabel('C\u00f3digo do Tribunal')
                           ?? $findValueByLabel('Codigo do Tribunal');

            $codOrgaoJud   = $findValueByLabel('Código do Órgão Judicial')
                           ?? $findValueByLabel('C\u00f3digo do \u00d3rg\u00e3o Judicial')
                           ?? $findValueByLabel('Codigo do Orgao Judicial');

            $numProcesso   = $findValueByLabel('Número do Processo')
                           ?? $findValueByLabel('N\u00famero do Processo')
                           ?? $findValueByLabel('Numero do Processo')
                           ?? $findValueByLabel('Número processo')
                           ?? $findValueByLabel('Numero processo');

            $anoProcesso   = $findValueByLabel('Ano de Processo')
                           ?? $findValueByLabel('Ano de processo')
                           ?? $findValueByLabel('Ano do Processo')
                           ?? $findValueByLabel('Ano do processo');

            $numeroProtocolo = $findValueByLabel('Número Protocolo')
                             ?? $findValueByLabel('Numero Protocolo');

            $anoProtocolo    = $findValueByLabel('Ano Protocolo')
                             ?? $findValueByLabel('Ano protocolo');

            $numeroOficio    = $findValueByLabel('Número de Ofício')
                             ?? $findValueByLabel('N\u00famero de Of\u00edcio')
                             ?? $findValueByLabel('Numero de Oficio');

            $anoOficio       = $findValueByLabel('Ano do Ofício')
                             ?? $findValueByLabel('Ano do Of\u00edcio')
                             ?? $findValueByLabel('Ano do Oficio');

            $municipioBloqueio = $findValueByLabel('Município do Bloqueio')
                               ?? $findValueByLabel('Municipio do Bloqueio')
                               ?? $findValueByLabel('Município Bloqueio')
                               ?? $findValueByLabel('Municipio Bloqueio');

            $motivoBloqueio    = $findValueByLabel('Motivo do Bloqueio')
                               ?? $findValueByLabel('Motivo Bloqueio');

            // "Nome do Órgão Judicial" ocupa toda a linha (colspan): o fallback q3 cobre
            $nomeOrgaoJud  = $findValueByLabel('Nome do Órgão Judicial')
                           ?? $findValueByLabel('Nome do \u00d3rg\u00e3o Judicial')
                           ?? $findValueByLabel('Nome do Orgao Judicial');

            // Montagem do JSON com a mesma semântica do HTML
            $out = [
                'fonte' => [
                    'titulo'    => 'eCRVsp - DETRAN - São Paulo',
                    'gerado_em' => $dataHora,
                ],
                'consulta' => [
                    'placa'           => $placa,
                    'municipio_placa' => $municipioPlaca,
                    'chassi'          => $chassi,
                    'quantidade' => [
                        'ocorrencias_encontradas' => $qtdEncontradas,
                        'ocorrencias_exibidas'    => $qtdExibidas,
                    ],
                ],
                'renajud' => [
                    [
                        'data_inclusao'           => $dataInclusao,
                        'hora_inclusao'           => $horaInclusao,
                        'tipo_restricao_judicial' => $tipoRestricao,
                        'tipo_bloqueio'           => $tipoRestricao,
                        'codigo_tribunal'         => $codTribunal,
                        'codigo_orgao_judicial'   => $codOrgaoJud,
                        'numero_processo'         => $numProcesso,
                        'ano_processo'            => $anoProcesso,
                        'nome_orgao_judicial'     => $nomeOrgaoJud,
                        'numero_protocolo'        => $numeroProtocolo,
                        'ano_protocolo'           => $anoProtocolo,
                        'numero_oficio'           => $numeroOficio,
                        'ano_oficio'              => $anoOficio,
                        'municipio_bloqueio'      => $municipioBloqueio,
                        'motivo_bloqueio'         => $motivoBloqueio,
                    ],
                ],
            ];

            // Sanitização básica (trim/normalização de "Nada Consta")
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
        // ---------- Fim parser ----------

        // ---------- Entrada ----------
        $opcao   = $request->query('opcaoPesquisa');
        $chassi  = $request->query('chassi');

        // Compatibilidade: aceitar captcha ou captchaResponse
        $captcha = $request->query('captcha');
        if (!$captcha) {
            $captcha = $request->query('captchaResponse');
        }

        // validação conforme seu script legado / front atual
        if (!in_array((string)$opcao, ['1', '2'], true)) {
            return response()->json(['message' => 'Valor inválido para opcaoPesquisa. Apenas 1 ou 2.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$chassi || !$captcha) {
            return response()->json(['message' => 'Informe chassi e captcha.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ---------- Token no admin_settings.value ----------
        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json(['message' => 'Nenhum token encontrado para realizar a consulta.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // ---------- Requisição ----------
        $headers = [
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language'           => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control'             => 'max-age=0',
            'Connection'                => 'keep-alive',
            'Content-Type'              => 'application/x-www-form-urlencoded',
            'Origin'                    => 'https://www.e-crvsp.sp.gov.br',
            'Referer'                   => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/bloqueiosAtivos.do',
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

        $form = [
            'method'          => 'pesquisar',
            'opcaoPesquisa'   => (string)$opcao,
            'chassi'          => strtoupper($chassi),
            'captchaResponse' => strtoupper($captcha), // servidor espera esse nome
        ];

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false]) // em produção, prefira manter verify=true
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID'      => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/bloqueiosAtivos.do', $form);

        // ---------- Saída JSON ----------
        $body = $parse($response->body());

        return response($body, Response::HTTP_OK)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }
}
