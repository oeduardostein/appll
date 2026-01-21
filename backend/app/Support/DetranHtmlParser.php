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
        $encoding = @mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8';
        $hasIsoCharset = stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false;

        if ($hasIsoCharset) {
            $encoding = 'ISO-8859-1';
        }

        if (strcasecmp($encoding, 'UTF-8') !== 0) {
            $html = @mb_convert_encoding($html, 'UTF-8', $encoding);
        }

        if ($hasIsoCharset) {
            $html = str_ireplace(['charset=iso-8859-1', 'charset=iso8859-1'], 'charset=utf-8', $html);
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $getText = static function (?DOMNode $n) {
            if (!$n) {
                return null;
            }

            return self::cleanText($n->textContent ?? '');
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
        $extractFieldsetData = static function (?DOMNode $context) use ($xp, $getText): array {
            if (! $context) {
                return [];
            }

            $data = [];
            $rows = $xp->query('.//td', $context);
            foreach ($rows as $cell) {
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
        $fieldsetNodes = $xp->query('//fieldset');

        foreach ($fieldsetNodes as $fieldset) {
            $legendNode = $xp->query('./legend', $fieldset)->item(0);
            $legendText = $legendNode ? $getText($legendNode) : null;

            if (self::normalizeString($legendText) !== 'comunicacao venda') {
                continue;
            }

            $buyerData = [];
            $intentionData = [];

            $childFieldsets = $xp->query('./fieldset', $fieldset);
            foreach ($childFieldsets as $childFieldset) {
                $childLegendNode = $xp->query('./legend', $childFieldset)->item(0);
                $childLegendText = $childLegendNode ? $getText($childLegendNode) : null;

                $normalizedChildLegend = self::normalizeString($childLegendText);

                if ($normalizedChildLegend === 'dados do comprador') {
                    $buyerData = $extractFieldsetData($childFieldset);
                }

                if ($normalizedChildLegend === 'dados da intencao de venda' || $normalizedChildLegend === 'dados da intenção de venda') {
                    $intentionData = $extractFieldsetData($childFieldset);
                }
            }

            $buyer = [
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
            ];

            $intention = [
                'uf'                    => $intentionData['UF'] ?? null,
                'estado'                => $intentionData['Estado Intenção Venda']
                    ?? ($intentionData['Estado Intencao Venda'] ?? null),
                'data_hora'             => $intentionData['Data/Hora'] ?? null,
                'data_hora_atualizacao' => $intentionData['Data/Hora Atualização']
                    ?? ($intentionData['Data/Hora Atualizacao'] ?? null),
                'valor_venda'           => $intentionData['Valor da venda']
                    ?? ($intentionData['Valor Venda'] ?? null),
            ];

            if (! array_filter($buyer) && ! array_filter($intention)) {
                continue;
            }

            $communications[] = [
                'comprador' => $buyer,
                'intencao'  => $intention,
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

        $comunicacaoVendas = !empty($communications)
            ? array_values($communications)
            : self::parseComunicacaoVendas($xp);

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
            'comunicacao_vendas' => $comunicacaoVendas,
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

    private static function parseComunicacaoVendas(DOMXPath $xpath): ?array
    {
        $fieldset = self::findFieldsetByLegend($xpath, 'Comunicação de Vendas');
        if (!$fieldset) {
            return null;
        }

        $pairs = self::extractLabelValuePairsFromNode($xpath, $fieldset);

        $status = $pairs['Comunicação de Vendas'] ?? null;
        $inclusao = $pairs['Inclusão'] ?? $pairs['Inclusão '] ?? $pairs['Inclusao'] ?? null;
        $tipoDoc = $pairs['Tipo Docto Comprador'] ?? null;
        $doc = $pairs['CNPJ / CPF do Comprador'] ?? $pairs['CNPJ/CPF do Comprador'] ?? null;
        $origem = $pairs['Origem'] ?? null;

        $datasNode = self::findDescendantFieldsetByLegend($xpath, $fieldset, 'Datas');
        $datasPairs = $datasNode ? self::extractLabelValuePairsFromNode($xpath, $datasNode) : [];

        $dataVenda = $datasPairs['Venda'] ?? null;
        $notaFiscal = $datasPairs['Nota Fiscal'] ?? null;
        $protocolo = $datasPairs['Protocolo Detran'] ?? $datasPairs['Protocolo DETRAN'] ?? null;

        $status = self::cleanText($status);
        $inclusao = self::cleanText($inclusao);
        $tipoDoc = self::cleanText($tipoDoc);
        $doc = self::cleanText($doc);
        $origem = self::cleanText($origem);
        $dataVenda = self::cleanText($dataVenda);
        $notaFiscal = self::cleanText($notaFiscal);
        $protocolo = self::cleanText($protocolo);

        if (!self::hasAnyValue([$status, $inclusao, $tipoDoc, $doc, $origem, $dataVenda, $notaFiscal, $protocolo])) {
            return null;
        }

        return [
            'status' => $status,
            'inclusao' => $inclusao,
            'tipo_documento_comprador' => $tipoDoc,
            'documento_comprador' => $doc,
            'origem' => $origem,
            'datas' => [
                'venda' => $dataVenda,
                'nota_fiscal' => $notaFiscal,
                'protocolo_detran' => $protocolo,
            ],
        ];
    }

    private static function findFieldsetByLegend(DOMXPath $xpath, string $legendText): ?\DOMElement
    {
        $query = "//fieldset[.//legend[contains(normalize-space(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇ', 'abcdefghijklmnopqrstuvwxyzáàãâäéèêëíìîïóòõôöúùûüç')), "
            . "normalize-space(translate('$legendText', 'ABCDEFGHIJKLMNOPQRSTUVWXYZÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇ', 'abcdefghijklmnopqrstuvwxyzáàãâäéèêëíìîïóòõôöúùûüç'))"
            . ")]]";

        $nodes = $xpath->query($query);
        if (!$nodes || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        return $node instanceof \DOMElement ? $node : null;
    }

    private static function findDescendantFieldsetByLegend(
        DOMXPath $xpath,
        \DOMElement $root,
        string $legendText
    ): ?\DOMElement {
        $query = ".//fieldset[.//legend[contains(normalize-space(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇ', 'abcdefghijklmnopqrstuvwxyzáàãâäéèêëíìîïóòõôöúùûüç')), "
            . "normalize-space(translate('$legendText', 'ABCDEFGHIJKLMNOPQRSTUVWXYZÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇ', 'abcdefghijklmnopqrstuvwxyzáàãâäéèêëíìîïóòõôöúùûüç'))"
            . ")]]";

        $nodes = $xpath->query($query, $root);
        if (!$nodes || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        return $node instanceof \DOMElement ? $node : null;
    }

    private static function extractLabelValuePairsFromNode(DOMXPath $xpath, \DOMElement $root): array
    {
        $spans = $xpath->query('.//span', $root);

        $out = [];
        $lastLabel = null;

        if (!$spans) {
            return $out;
        }

        foreach ($spans as $span) {
            if (!$span instanceof \DOMElement) {
                continue;
            }

            $class = ' ' . ($span->getAttribute('class') ?? '') . ' ';
            $text = self::cleanText($span->textContent);

            if ($text === null) {
                continue;
            }

            if (str_contains($class, ' texto_black2 ')) {
                $lastLabel = $text;
                continue;
            }

            if (str_contains($class, ' texto_menor ') || str_contains($class, ' fonte_legend ')) {
                if ($lastLabel) {
                    $out[$lastLabel] = $text;
                    $lastLabel = null;
                }
            }
        }

        return $out;
    }

    private static function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $v = preg_replace('/\s+/u', ' ', $v);
        $v = trim($v);

        return $v === '' ? null : $v;
    }

    private static function hasAnyValue(array $values): bool
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private static function normalizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        $map = [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'Ê' => 'E', 'È' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];

        $value = strtr($value, $map);

        return mb_strtolower($value);
    }
}
