<?php

namespace App\Support;

final class FichaCadastralHttp
{
    private function __construct()
    {
    }

    public static function headers(string $referer): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => $referer,
            'Sec-Fetch-Dest' => 'frame',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"macOS"',
        ];
    }

    public static function cookies(string $token): array
    {
        return [
            'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
            'JSESSIONID' => $token,
        ];
    }

    public static function extractJsonMessage(string $body): ?string
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded) && isset($decoded['message'])) {
            $message = trim((string) $decoded['message']);
            return $message === '' ? null : $message;
        }

        return null;
    }

    public static function extractHtmlMessage(string $html): ?string
    {
        if (preg_match_all("/errors\\[[^\\]]*\\]\\s*=\\s*'([^']+)'/u", $html, $matches) && !empty($matches[1])) {
            $messages = array_map(static fn ($msg) => html_entity_decode($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches[1]);
            $message = trim(implode(' ', $messages));
            if ($message !== '') {
                return $message;
            }
        }

        if (preg_match("/alert\\s*\\(\\s*'([^']+)'\\s*\\)/u", $html, $matches)) {
            $message = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($message !== '') {
                return $message;
            }
        }

        return null;
    }
}
