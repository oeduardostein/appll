<?php

namespace App\Services;

use App\Support\PlacaZeroKmParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlacasZeroKmConsultaService
{
    /**
     * @param array{
     *   cpf_cgc: string,
     *   chassi: string,
     *   numeros?: string|null,
     *   numero_tentativa?: int|string|null,
     *   tipo_restricao_financeira?: string|int|null,
     *   placa_escolha_anterior?: string|null,
     *   prefixes?: string[]|null,
     *   debug?: bool|null
     * } $input
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function consultar(array $input): array
    {
        $cpfCgc = preg_replace('/\D/', '', (string) ($input['cpf_cgc'] ?? '')) ?? '';
        $chassi = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($input['chassi'] ?? '')) ?? '');
        $numeros = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($input['numeros'] ?? '')) ?? '');
        $numeroTentativa = (int) ($input['numero_tentativa'] ?? 3);
        $tipoRestricao = (string) ($input['tipo_restricao_financeira'] ?? '-1');
        $placaEscolhaAnterior = normalize_plate((string) ($input['placa_escolha_anterior'] ?? ''));
        $debug = (bool) ($input['debug'] ?? false);

        if ($cpfCgc === '' || !in_array(strlen($cpfCgc), [11, 14], true)) {
            return [
                'success' => false,
                'error' => 'Informe um CPF/CNPJ válido.',
            ];
        }

        if ($chassi === '' || strlen($chassi) < 17) {
            return [
                'success' => false,
                'error' => 'Informe um chassi válido.',
            ];
        }

        if ($numeros !== '' && strlen($numeros) > 4) {
            return [
                'success' => false,
                'error' => 'O complemento deve ter até 4 caracteres.',
            ];
        }

        $prefixes = $this->normalizePrefixes($input['prefixes'] ?? null);
        if (empty($prefixes)) {
            $prefixes = [
                'QSY',
                'UEH',
                'UET',
                'UGP',
                'TJX',
                'UEJ',
                'UEV',
                'UGT',
                'UDJ',
                'UEJ',
                'UFB',
                'UGU',
                'UDR',
                'UEP',
                'UGB',
                'UDX',
                'UET',
                'UGM',
            ];
        }

        $token = (string) DB::table('admin_settings')->where('id', 1)->value('value');
        if ($token === '') {
            return [
                'success' => false,
                'error' => 'Token de sessão não configurado.',
            ];
        }

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/escolhaPlaca.do',
            'Sec-Fetch-Dest' => 'frame',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Chromium";v="144", "Not:A-Brand";v="8", "Google Chrome";v="144"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
        ];

        $results = [];
        $platesLast = [];

        foreach ($prefixes as $prefix) {
            $payload = [
                'method' => 'pesquisarPlaca',
                'cpfCgcProprietario' => $cpfCgc,
                'tipoRestricaoFinanceira' => $tipoRestricao,
                'chassi' => $chassi,
                'cpfCgcProprietarioFormatado' => $this->formatCpfCnpj($cpfCgc),
                'placaEscolhaAnterior' => $placaEscolhaAnterior,
                'numeroTentativa' => (string) $numeroTentativa,
                'letras' => $prefix,
                'numeros' => $numeros,
            ];

            $parsed = $this->query($payload, $headers, $token, $cpfCgc, $chassi);
            if ($parsed === null) {
                if ($debug) {
                    $results[] = [
                        'prefix' => $prefix,
                        'error' => 'Falha ao consultar o serviço do DETRAN.',
                    ];
                }
                continue;
            }

            $available = $parsed['placas_disponiveis'] ?? [];
            $lastPlate = !empty($available) ? end($available) : null;
            if ($lastPlate) {
                $platesLast[] = $lastPlate;
            }

            if ($debug) {
                $results[] = [
                    'prefix' => $prefix,
                    'data' => $parsed,
                    'last_plate' => $lastPlate,
                ];
            }
        }

        $data = [
            'cpf_cgc' => $cpfCgc,
            'chassi' => $chassi,
            'numeros' => $numeros !== '' ? $numeros : null,
            'placas' => array_values(array_unique($platesLast)),
        ];

        if ($debug) {
            $data['resultados'] = $results;
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    private function formatCpfCnpj(string $digits): string
    {
        if (strlen($digits) === 11) {
            return sprintf(
                '%s.%s.%s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 3),
                substr($digits, 9, 2)
            );
        }

        if (strlen($digits) === 14) {
            return sprintf(
                '%s.%s.%s/%s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 3),
                substr($digits, 5, 3),
                substr($digits, 8, 4),
                substr($digits, 12, 2)
            );
        }

        return $digits;
    }

    /**
     * @return string[]
     */
    private function normalizePrefixes(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            $prefix = strtoupper(preg_replace('/[^A-Z]/', '', (string) $item));
            if (strlen($prefix) !== 3) {
                continue;
            }
            $out[] = $prefix;
        }

        return array_values(array_unique($out));
    }

    private function query(array $payload, array $headers, string $token, string $cpfCgc, string $chassi): ?array
    {
        try {
            $cookieTimestamp = now()->setTimezone('America/Sao_Paulo')->format('D M d Y H:i:s');
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->withOptions(['verify' => false])
                ->withCookies([
                    'dataUsuarPublic' => $cookieTimestamp . ' GMT-0300 (Horário Padrão de Brasília)',
                    'naoExibirPublic' => 'sim',
                    'JSESSIONID' => $token,
                ], 'www.e-crvsp.sp.gov.br')
                ->asForm()
                ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/escolhaPlaca.do', $payload);

            if (!$response->successful()) {
                Log::warning('Placas 0KM: Falha na requisição externa', [
                    'status' => $response->status(),
                    'cpf_cgc' => $cpfCgc,
                    'chassi' => $chassi,
                    'response_body' => substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return PlacaZeroKmParser::parse($response->body());
        } catch (\Throwable $e) {
            Log::error('Placas 0KM: Erro de conexão', [
                'message' => $e->getMessage(),
                'cpf_cgc' => $cpfCgc,
                'chassi' => $chassi,
            ]);
            return null;
        }
    }
}

