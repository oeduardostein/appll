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

class AnotherBaseStateController extends Controller
{
    /**
     * Consulta a base de Outros Estados utilizando o serviço externo do Detran SP.
     */
    public function __invoke(Request $request): Response
    {
        // --- Função de parsing (igual à base do seu controller atual) ---
        function parseDetranHtmlToArray(string $html): array {
            // 1) Garantir UTF-8 (a página declara ISO-8859-1)
            if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
                $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
            }

            // 2) DOM + XPath
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new DOMXPath($dom);

            // Helpers
            $norm = fn($s) => preg_replace('/\s+/u', ' ', trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $getText = function(?DOMNode $n) use ($norm) {
                if (!$n) return null;
                return $norm($n->textContent ?? '');
            };

            // Busca valor por label (layout DETRAN)
            $findValueByLabel = function(string $label) use ($xp, $getText) {
                // match exato
                $q1 = sprintf(
                    '//span[contains(@class,"texto_black2")][normalize-space()="%s"]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q1)->item(0);
                if ($n) return $getText($n);

                // contém
                $q2 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q2)->item(0);
                if ($n) return $getText($n);

                // colspan
                $q3 = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td//span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q3)->item(0);
                if ($n) return $getText($n);

                return null;
            };

            // Busca inline
            $findInlineValue = function(string $label) use ($xp, $getText) {
                $q = sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/following::span[contains(@class,"texto_menor")][1]',
                    $label
                );
                $n = $xp->query($q)->item(0);
                return $n ? $getText($n) : null;
            };

            // Data/hora impressa no HTML
            $bodyText = $xp->query('//body')->item(0);
            $allText = $getText($bodyText);
            $dataHora = null;
            if ($allText && preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $allText, $m)) {
                $dataHora = $m[1];
            }

            // Labels
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

                'Grav_RestricaoFinanceira'  => ['Restrição Financeira', 'Restricao Financeira'],
                'Grav_NomeAgente'           => ['Nome Agente'],
                'Grav_Arrendatario'         => ['Arrendatário/ Financiado', 'Arrendatario/ Financiado'],
                'Grav_CnpjCpf'              => ['CNPJ/CPF Financ', 'CNPJ/CPF Financ.'],

                'Deb_DERSA'                 => ['DERSA'],
                'Deb_DER'                   => ['DER'],
                'Deb_DETRAN'                => ['DETRAN'],
                'Deb_CETESB'                => ['CETESB'],
                'Deb_RENAINF'               => ['Renainf'],
                'Deb_Municipais'            => ['Municipais'],
                'Deb_PRF'                   => ['Polícia Rodoviária Federal', 'Policia Rodoviaria Federal'],
                'Deb_IPVA'                  => ['IPVA'],

                'Rest_Furto'                => ['Restrições Furto', 'Restricoes Furto'],
                'Rest_Guincho'              => ['Bloqueio de Guincho'],
                'Rest_Administrativas'      => ['Restrições Administrativas', 'Restricoes Administrativas'],
                'Rest_Judicial'             => ['Restrições Judicial', 'Restricoes Judicial'],
                'Rest_Tributaria'           => ['Restrições Tributária', 'Restricoes Tributaria'],
                'Rest_RENAJUD'              => ['Bloqueios RENAJUD'],
                'Rest_InspecaoAmbiental'    => ['Inspeção Ambiental', 'Inspecao Ambiental'],

                'CRV_Exercicio'             => ['Exerc. Licenciamento', 'Exercício Licenciamento', 'Exerc Licenciamento'],
                'CRV_DataLic'               => ['Licenciamento'],

                'CV_Comunicacao'            => ['Comunicação de Vendas', 'Comunicacao de Vendas'],
                'CV_Inclusao'               => ['Inclusão', 'Inclusao'],
                'CV_TipoDocComprador'       => ['Tipo Docto Comprador'],
                'CV_CnpjCpfComprador'       => ['CNPJ / CPF do Comprador', 'CNPJ/CPF do Comprador'],
                'CV_Origem'                 => ['Origem'],
                'CV_DataVenda'              => ['Venda'],
                'CV_NotaFiscal'             => ['Nota Fiscal'],
                'CV_ProtocoloDetran'        => ['Protocolo Detran'],
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
                'gravames' => [
                    'restricao_financeira' => $pick($L['Grav_RestricaoFinanceira']),
                    'nome_agente'          => $pick($L['Grav_NomeAgente']),
                    'arrendatario'         => $pick($L['Grav_Arrendatario']),
                    'cnpj_cpf_financiado'  => $pick($L['Grav_CnpjCpf']),
                    'datas'                => [
                        'inclusao_financiamento' => $pick(['Inclusão Financiamento','Inclusao Financiamento']),
                    ],
                ],
                'intencao_gravame' => [
                    'restricao_financeira' => $pick(['Restr. Financeira','Restr. Financeira']),
                    'agente_financeiro'    => $pick(['Agente Financeiro']),
                    'nome_financiado'      => $pick(['Nome do Financiado']),
                    'cnpj_cpf'             => $pick(['CNPJ/CPF Financ','CNPJ/CPF Financ.']),
                    'data_inclusao'        => $pick(['Data Inclusão','Data Inclusao']),
                ],
                'debitos_multas' => [
                    'dersa'       => $pick($L['Deb_DERSA']),
                    'der'         => $pick($L['Deb_DER']),
                    'detran'      => $pick($L['Deb_DETRAN']),
                    'cetesb'      => $pick($L['Deb_CETESB']),
                    'renainf'     => $pick($L['Deb_RENAINF']),
                    'municipais'  => $pick($L['Deb_Municipais']),
                    'prf'         => $pick($L['Deb_PRF']),
                    'ipva'        => $pick($L['Deb_IPVA']),
                ],
                'restricoes' => [
                    'furto'              => $pick($L['Rest_Furto']),
                    'bloqueio_guincho'   => $pick($L['Rest_Guincho']),
                    'administrativas'    => $pick($L['Rest_Administrativas']),
                    'judicial'           => $pick($L['Rest_Judicial']),
                    'tributaria'         => $pick($L['Rest_Tributaria']),
                    'renajud'            => $pick($L['Rest_RENAJUD']),
                    'inspecao_ambiental' => $pick($L['Rest_InspecaoAmbiental']),
                ],
                'crv_crlv_atualizacao' => [
                    'exercicio_licenciamento' => $pick($L['CRV_Exercicio']),
                    'data_licenciamento'      => $pick($L['CRV_DataLic']),
                ],
                'comunicacao_vendas' => [
                    'status'             => $pick($L['CV_Comunicacao']),
                    'inclusao'           => $pick($L['CV_Inclusao']),
                    'tipo_doc_comprador' => $pick($L['CV_TipoDocComprador']),
                    'cnpj_cpf_comprador' => $pick($L['CV_CnpjCpfComprador']),
                    'origem'             => $pick($L['CV_Origem']),
                    'datas'              => [
                        'venda'            => $pick($L['CV_DataVenda']),
                        'nota_fiscal'      => $pick($L['CV_NotaFiscal']),
                        'protocolo_detran' => $pick($L['CV_ProtocoloDetran']),
                    ],
                ],
            ];

            // 3) Sanitização
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

            return $out;
        }

        // --- Entrada via query string ---
        $chassi   = $request->query('chassi');
        $uf       = $request->query('uf');
        $captcha  = $request->query('captcha');

        if (!$chassi || !$uf || !$captcha) {
            return response()->json(
                ['message' => 'Informe chassi, uf e captcha para realizar a consulta.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // --- Token via admin_settings.value (mesmo padrão do seu controller atual) ---
        $token = DB::table('admin_settings')->where('id', 1)->value('value');
        if (!$token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a consulta.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // --- Headers / Cookies ---
        $headers = [
            'Accept'                 => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language'        => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control'          => 'max-age=0',
            'Connection'             => 'keep-alive',
            'Content-Type'           => 'application/x-www-form-urlencoded',
            'Origin'                 => 'https://www.e-crvsp.sp.gov.br',
            'Referer'                => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseOutrosEstados.do',
            'Sec-Fetch-Dest'         => 'frame',
            'Sec-Fetch-Mode'         => 'navigate',
            'Sec-Fetch-Site'         => 'same-origin',
            'Sec-Fetch-User'         => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent'             => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'sec-ch-ua'              => '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile'       => '?0',
            'sec-ch-ua-platform'     => '"macOS"',
        ];
        $cookieDomain = 'www.e-crvsp.sp.gov.br';

        // --- Chamada ao endpoint de "Outros Estados" ---
        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false]) // se necessário em ambiente de homolog; em prod prefira manter verify=true
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID'      => $token,
            ], $cookieDomain)
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseOutrosEstados.do', [
                'method'          => 'pesquisar',
                'chassi'          => strtoupper($chassi),
                'uf'              => strtoupper($uf),
                'captchaResponse' => strtoupper($captcha),
            ]);
        if (!$response->successful()) {
            return response()->json(
                ['message' => 'Falha ao consultar a base externa (Outros Estados).'],
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

        $parsed = parseDetranHtmlToArray($body);

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
