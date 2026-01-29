<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;

class PlacaZeroKmParser
{
    public static function parse(string $html): array
    {
        if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
            $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1');
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $normalizeText = static fn(?string $value): ?string => $value === null ? null : trim(preg_replace('/\s+/u', ' ', $value));
        $extractInputValue = static function (DOMXPath $xpath, string $name) use ($normalizeText): ?string {
            $node = $xpath->query(sprintf('//input[@name="%s"]', $name))->item(0);
            if (!$node) {
                return null;
            }
            $value = $node->getAttribute('value');
            return $normalizeText($value !== '' ? $value : null);
        };

        $consulta = [
            'cpf_cgc' => $extractInputValue($xpath, 'cpfCgcProprietario'),
            'cpf_cgc_formatado' => $extractInputValue($xpath, 'cpfCgcProprietarioFormatado'),
            'chassi' => $extractInputValue($xpath, 'chassi'),
            'placa_escolha_anterior' => $extractInputValue($xpath, 'placaEscolhaAnterior'),
            'numero_tentativa' => $extractInputValue($xpath, 'numeroTentativa'),
            'tipo_restricao_financeira' => $extractInputValue($xpath, 'tipoRestricaoFinanceira'),
        ];

        $placaSelecionada = null;
        $placaSelecionadaNode = $xpath->query('//*[@id="nomePlaca"]')->item(0);
        if ($placaSelecionadaNode) {
            $placaSelecionada = $normalizeText($placaSelecionadaNode->textContent);
        }

        $placas = [];
        $plateRegex = '/\\b[A-Z]{3}[0-9][A-Z0-9][0-9]{2}\\b/';
        $listContainer = $xpath->query('//*[@id="divListaPlaca"]')->item(0);
        if ($listContainer) {
            $text = strtoupper($listContainer->textContent ?? '');
            if (preg_match_all($plateRegex, $text, $matches)) {
                $placas = $matches[0];
            }
        }

        if (empty($placas)) {
            $possibleNodes = $xpath->query('//*[contains(@class,"placa") or contains(@id,"placa")]');
            if ($possibleNodes) {
                $buffer = '';
                foreach ($possibleNodes as $node) {
                    $buffer .= ' ' . ($node->textContent ?? '');
                }
                $buffer = strtoupper($buffer);
                if (preg_match_all($plateRegex, $buffer, $matches)) {
                    $placas = $matches[0];
                }
            }
        }

        $placas = array_values(array_unique(array_filter($placas)));

        return [
            'consulta' => array_filter($consulta, static fn($value) => $value !== null && $value !== ''),
            'placa_selecionada' => $placaSelecionada,
            'placas_disponiveis' => $placas,
            'errors' => self::extractMessages($html, 'errors'),
            'messages' => self::extractMessages($html, 'messages'),
        ];
    }

    /**
     * @return string[]
     */
    private static function extractMessages(string $html, string $bucket): array
    {
        if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
            $html = @mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        $pattern = sprintf('/%s\\[%s\\.length\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;?/iu', preg_quote($bucket, '/'), preg_quote($bucket, '/'));
        if (!preg_match_all($pattern, $html, $matches)) {
            return [];
        }

        $messages = [];
        foreach ($matches[1] as $message) {
            $messages[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $messages;
    }
}
