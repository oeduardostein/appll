<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMNode;
use DOMXPath;

class DetranHtmlParser
{
    /**
     * Parse the DETRAN HTML response into a structured associative array.
     *
     * @return array<string, mixed>
     */
    public static function parse(string $html): array
    {
        if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
            $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $norm = static fn ($s) => preg_replace(
            '/\s+/u',
            ' ',
            trim(html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        );
        $getText = static function (?DOMNode $n) use ($norm) {
            if (!$n) {
                return null;
            }

            return $norm($n->textContent ?? '');
        };

        $findValueByLabel = static function (string $label) use ($xp, $getText) {
            $queries = [
                sprintf(
                    '//span[contains(@class,"texto_black2")][normalize-space()="%s"]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                ),
                sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td[1]//span[contains(@class,"texto_menor")][1]',
                    $label
                ),
                sprintf(
                    '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/ancestor::td[1]/following-sibling::td//span[contains(@class,"texto_menor")][1]',
                    $label
                ),
            ];

            foreach ($queries as $query) {
                $node = $xp->query($query)->item(0);
                if ($node) {
                    return $getText($node);
                }
            }

            return null;
        };

        $findInlineValue = static function (string $label) use ($xp, $getText) {
            $query = sprintf(
                '//span[contains(@class,"texto_black2")][contains(normalize-space(), "%s")]/following::span[contains(@class,"texto_menor")][1]',
                $label
            );

            $node = $xp->query($query)->item(0);

            return $node ? $getText($node) : null;
        };

        $extractFieldsetData = static function (?DOMNode $context) use ($xp, $getText) {
            if (! $context) {
                return [];
            }

            $data = [];
            $cells = $xp->query('.//td', $context);
            foreach ($cells as $cell) {
                $labelNode = $xp->query('.//span[contains(@class,"texto_black2")]', $cell)->item(0);
                $valueNode = $xp->query('.//span[contains(@class,"texto_menor")]', $cell)->item(0);

                $label = $labelNode ? $getText($labelNode) : null;
                $value = $valueNode ? $getText($valueNode) : null;

                if ($label === null || $label === '' || $value === null || $value === '') {
                    continue;
                }

                $data[$label] = $value;
            }

            return $data;
        };

        $communications = [];
        $communicationFieldsets = $xp->query('//fieldset[legend/font[contains(normalize-space(.), "Comunicação Venda")]]');

        foreach ($communicationFieldsets as $fieldset) {
            $buyerFieldset = $xp->query('.//fieldset[legend/font[contains(normalize-space(.), "Dados do Comprador")]]', $fieldset)->item(0);
            $intentionFieldset = $xp->query('.//fieldset[legend/font[contains(normalize-space(.), "Dados da Intenção de Venda")]]', $fieldset)->item(0);

            $buyerData = $extractFieldsetData($buyerFieldset);
            $intentionData = $extractFieldsetData($intentionFieldset);

            $communications[] = [
                'comprador' => [
                    'documento'   => $buyerData['CPF/CNPJ'] ?? ($buyerData['CPF / CNPJ'] ?? null),
                    'nome'        => $buyerData['Nome'] ?? ($buyerData['Nome do Comprador'] ?? null),
                    'email'       => $buyerData['E-mail'] ?? ($buyerData['Email'] ?? null),
                    'uf'          => $buyerData['UF'] ?? null,
                    'municipio'   => $buyerData['Município'] ?? ($buyerData['Municipio'] ?? null),
                    'bairro'      => $buyerData['Bairro'] ?? null,
                    'logradouro'  => $buyerData['Logradouro'] ?? null,
                    'numero'      => $buyerData['Número'] ?? ($buyerData['Numero'] ?? null),
                    'complemento' => $buyerData['Complemento'] ?? null,
                    'cep'         => $buyerData['CEP'] ?? null,
                ],
                'intencao' => [
                    'uf'                   => $intentionData['UF'] ?? null,
                    'estado'               => $intentionData['Estado Intenção Venda']
                        ?? ($intentionData['Estado Intencao Venda'] ?? null),
                    'data_hora'            => $intentionData['Data/Hora'] ?? null,
                    'data_hora_atualizacao' => $intentionData['Data/Hora Atualização']
                        ?? ($intentionData['Data/Hora Atualizacao'] ?? null),
                    'valor_venda'          => $intentionData['Valor da venda'] ?? null,
                ],
            ];
        }

        $bodyText = $xp->query('//body')->item(0);
        $allText = $getText($bodyText);
        $dataHora = null;
        if ($allText && preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $allText, $match)) {
            $dataHora = $match[1];
        }

        $labels = [
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
            'AnoFab'                    => ['Ano Fabr.', 'Ano Fabr', 'Ano' . "\n\t\t\t\t\t\t\t\t\t\t" . 'Fabr.'],
            'AnoModelo'                 => ['Ano Modelo', 'Ano' . "\n\t\t\t\t\t\t\t\t\t\t" . 'Modelo'],

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

        $pick = static function (array $options) use ($findValueByLabel, $findInlineValue) {
            foreach ($options as $label) {
                $value = $findValueByLabel($label);
                if ($value !== null && $value !== '') {
                    return $value;
                }
                $value = $findInlineValue($label);
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            return null;
        };

        $output = [
            'fonte' => [
                'titulo'    => $pick(['eCRVsp - DETRAN - São Paulo']) ?? 'eCRVsp - DETRAN - São Paulo',
                'gerado_em' => $dataHora,
            ],
            'veiculo' => [
                'placa'          => $pick($labels['Placa']),
                'municipio'      => $pick($labels['Municipio']),
                'renavam'        => $pick($labels['Renavam']),
                'chassi'         => $pick($labels['Chassi']),
                'tipo'           => $pick($labels['Tipo']),
                'procedencia'    => $pick($labels['Procedencia']),
                'combustivel'    => $pick($labels['Combustivel']),
                'cor'            => $pick($labels['Cor']),
                'marca'          => $pick($labels['Marca']),
                'categoria'      => $pick($labels['Categoria']),
                'ano_fabricacao' => $pick($labels['AnoFab']),
                'ano_modelo'     => $pick($labels['AnoModelo']),
            ],
            'proprietario' => [
                'nome' => $pick($labels['Proprietario']),
            ],
            'gravames' => [
                'restricao_financeira' => $pick($labels['Grav_RestricaoFinanceira']),
                'nome_agente'          => $pick($labels['Grav_NomeAgente']),
                'arrendatario'         => $pick($labels['Grav_Arrendatario']),
                'cnpj_cpf_financiado'  => $pick($labels['Grav_CnpjCpf']),
                'datas'                => [
                    'inclusao_financiamento' => $pick(['Inclusão Financiamento', 'Inclusao Financiamento']),
                ],
            ],
            'intencao_gravame' => [
                'restricao_financeira' => $pick(['Restr. Financeira', 'Restr. Financeira']),
                'agente_financeiro'    => $pick(['Agente Financeiro']),
                'nome_financiado'      => $pick(['Nome do Financiado']),
                'cnpj_cpf'             => $pick(['CNPJ/CPF Financ', 'CNPJ/CPF Financ.']),
                'data_inclusao'        => $pick(['Data Inclusão', 'Data Inclusao']),
            ],
            'debitos_multas' => [
                'dersa'      => $pick($labels['Deb_DERSA']),
                'der'        => $pick($labels['Deb_DER']),
                'detran'     => $pick($labels['Deb_DETRAN']),
                'cetesb'     => $pick($labels['Deb_CETESB']),
                'renainf'    => $pick($labels['Deb_RENAINF']),
                'municipais' => $pick($labels['Deb_Municipais']),
                'prf'        => $pick($labels['Deb_PRF']),
                'ipva'       => $pick($labels['Deb_IPVA']),
            ],
            'restricoes' => [
                'furto'              => $pick($labels['Rest_Furto']),
                'bloqueio_guincho'   => $pick($labels['Rest_Guincho']),
                'administrativas'    => $pick($labels['Rest_Administrativas']),
                'judicial'           => $pick($labels['Rest_Judicial']),
                'tributaria'         => $pick($labels['Rest_Tributaria']),
                'renajud'            => $pick($labels['Rest_RENAJUD']),
                'inspecao_ambiental' => $pick($labels['Rest_InspecaoAmbiental']),
            ],
            'crv_crlv_atualizacao' => [
                'exercicio_licenciamento' => $pick($labels['CRV_Exercicio']),
                'data_licenciamento'      => $pick($labels['CRV_DataLic']),
            ],
            'comunicacao_vendas' => array_values(array_filter($communications, static function ($item) {
                if (! is_array($item)) {
                    return false;
                }

                $comprador = $item['comprador'] ?? [];
                $intencao = $item['intencao'] ?? [];

                return (is_array($comprador) && array_filter($comprador)) || (is_array($intencao) && array_filter($intencao));
            })),
        ];

        $sanitize = static function (&$value) use (&$sanitize) {
            if (is_array($value)) {
                foreach ($value as &$nested) {
                    $sanitize($nested);
                }
                return;
            }

            if ($value === null) {
                return;
            }

            $value = preg_replace('/\s+/u', ' ', trim((string) $value));
            if (preg_match('/^n\s*a\s*d\s*a\s*consta$/iu', preg_replace('/\s+/', '', $value))) {
                $value = 'Nada Consta';
            }
        };

        $sanitize($output);

        return $output;
    }
}
