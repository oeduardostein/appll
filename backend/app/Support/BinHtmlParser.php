<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMNode;
use DOMXPath;

class BinHtmlParser
{
    /**
     * Parse BIN HTML response into structured sections.
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
        $xpath = new DOMXPath($dom);

        $sections = [];
        $normalized = [];

        /** @var \DOMNodeList $fieldsets */
        $fieldsets = $xpath->query('//fieldset');
        if ($fieldsets !== false) {
            foreach ($fieldsets as $fieldset) {
                $legendNode = $xpath->query('./legend', $fieldset)->item(0);
                $title = self::normalizeWhitespace($legendNode?->textContent ?? '');
                if ($title === '') {
                    continue;
                }

                $sectionSlug = self::slugify($title);
                $items = self::extractItems($xpath, $fieldset);

                $sections[] = [
                    'title' => $title,
                    'slug' => $sectionSlug,
                    'items' => $items,
                ];

                if (!isset($normalized[$sectionSlug])) {
                    $normalized[$sectionSlug] = [];
                }
                foreach ($items as $item) {
                    $normalized[$sectionSlug][$item['slug']] = $item['value'];
                }
            }
        }

        $timestamp = null;
        $bodyTextNode = $xpath->query('//body')->item(0);
        if ($bodyTextNode instanceof DOMNode) {
            $bodyText = self::normalizeWhitespace($bodyTextNode->textContent ?? '');
            if (preg_match('/\b(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\b/u', $bodyText, $match)) {
                $timestamp = $match[1];
            }
        }

        return [
            'fonte' => [
                'titulo' => 'eCRVsp - DETRAN - São Paulo',
                'gerado_em' => $timestamp,
            ],
            'sections' => $sections,
            'normalized' => $normalized,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function extractItems(DOMXPath $xpath, DOMNode $fieldset): array
    {
        $labels = $xpath->query('.//span[contains(@class,"texto_black2")]', $fieldset);
        $items = [];

        if ($labels === false) {
            return $items;
        }

        foreach ($labels as $labelNode) {
            $label = self::normalizeWhitespace($labelNode->textContent ?? '');
            if ($label === '') {
                continue;
            }

            // Find the nearest texto_menor following this label within the same row or cell.
            $valueNode = $xpath->query(
                'following::span[contains(@class,"texto_menor")][1]',
                $labelNode
            )->item(0);

            $value = self::normalizeWhitespace($valueNode?->textContent ?? '');

            $items[] = [
                'label' => $label,
                'value' => $value,
                'slug' => self::slugify($label),
            ];
        }

        return $items;
    }

    private static function normalizeWhitespace(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $decoded));
    }

    private static function slugify(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', (string) $value);
        $value = trim($value, '_');

        return $value === '' ? 'campo' : $value;
    }
}
