<?php

namespace App\Http\Controllers\Api;

use App\Models\AtpvRequest;
use App\Support\DetranHtmlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EmitirAtpvController extends BaseAtpvController
{
    public function __invoke(Request $request): Response
    {
        $user = $this->findUserFromRequest($request);

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        $data = $request->validate([
            'renavam' => ['required', 'string', 'max:20'],
            'placa' => ['required', 'string', 'max:10'],
            'chassi' => ['nullable', 'string', 'max:32'],
            'hodometro' => ['nullable', 'string', 'max:16'],
            'email_proprietario' => ['nullable', 'string', 'max:150'],
            'cpf_cnpj_proprietario' => ['nullable', 'string', 'max:20'],
            'cpf_cnpj_comprador' => ['required', 'string', 'max:20'],
            'nome_comprador' => ['required', 'string', 'max:150'],
            'email_comprador' => ['nullable', 'string', 'max:150'],
            'uf' => ['required', 'string', 'max:2'],
            'valor_venda' => ['nullable', 'string', 'max:32'],
            'cep_comprador' => ['nullable', 'string', 'max:16'],
            'municipio_comprador' => ['nullable', 'string', 'max:150'],
            'bairro_comprador' => ['nullable', 'string', 'max:150'],
            'logradouro_comprador' => ['nullable', 'string', 'max:150'],
            'numero_comprador' => ['nullable', 'string', 'max:20'],
            'complemento_comprador' => ['nullable', 'string', 'max:100'],
            'municipio_codigo' => ['nullable', 'string', 'max:10'],
            'captcha' => ['required', 'string', 'max:12'],
        ]);

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (! $token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a emissão.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $municipioCodigo = $data['municipio_codigo'] ?? null;
        if ($municipioCodigo !== null) {
            $municipioCodigo = preg_replace('/\D/', '', (string) $municipioCodigo);
            if ($municipioCodigo === '') {
                $municipioCodigo = null;
            }
        }

        $record = AtpvRequest::create([
            'user_id' => $user->id,
            'renavam' => $data['renavam'],
            'placa' => strtoupper($data['placa']),
            'chassi' => $data['chassi'] ?? null,
            'hodometro' => $data['hodometro'] ?? null,
            'email_proprietario' => $data['email_proprietario'] ?? null,
            'cpf_cnpj_proprietario' => $data['cpf_cnpj_proprietario'] ?? null,
            'cpf_cnpj_comprador' => $data['cpf_cnpj_comprador'],
            'nome_comprador' => $data['nome_comprador'],
            'email_comprador' => $data['email_comprador'] ?? null,
            'uf' => strtoupper($data['uf']),
            'valor_venda' => $data['valor_venda'] ?? null,
            'cep_comprador' => $data['cep_comprador'] ?? null,
            'municipio_comprador' => $data['municipio_comprador'] ?? null,
            'bairro_comprador' => $data['bairro_comprador'] ?? null,
            'logradouro_comprador' => $data['logradouro_comprador'] ?? null,
            'numero_comprador' => $data['numero_comprador'] ?? null,
            'complemento_comprador' => $data['complemento_comprador'] ?? null,
            'status' => 'pending',
            'municipio_codigo' => $municipioCodigo,
        ]);

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/incluirIntencaoVenda.do',
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

        [$opcaoPesquisaProprietario, $cpfProprietario, $cnpjProprietario] = $this->splitDocumento(
            $record->cpf_cnpj_proprietario
        );
        [$opcaoPesquisaComprador, $cpfComprador, $cnpjComprador] = $this->splitDocumento(
            $record->cpf_cnpj_comprador
        );

        $form = [
            'method' => 'pesquisar',
            'municipio2' => $record->municipio_codigo ?? '0',
            'renavam' => $record->renavam,
            'placa' => $record->placa,
            'chassi' => $record->chassi ?? '',
            'hodometro' => $record->hodometro ?? '',
            'emailProprietario' => $record->email_proprietario ?? '',
            'opcaoPesquisaProprietario' => $opcaoPesquisaProprietario,
            'cpfProprietario' => $cpfProprietario,
            'cnpjProprietario' => $cnpjProprietario,
            'opcaoPesquisaComprador' => $opcaoPesquisaComprador,
            'cpfComprador' => $cpfComprador,
            'cnpjComprador' => $cnpjComprador,
            'nomeComprador' => $record->nome_comprador ?? '',
            'emailComprador' => $record->email_comprador ?? '',
            'valorVendaSTR' => $this->formatValorVenda($record->valor_venda),
            'cepComprador' => $record->cep_comprador ?? '',
            'municipioComprador' => $this->toUpper($record->municipio_comprador),
            'bairro' => $this->toUpper($record->bairro_comprador),
            'logradouro' => $this->toUpper($record->logradouro_comprador),
            'numeroComprador' => $record->numero_comprador ?? '',
            'complementoComprador' => $record->complemento_comprador ?? '',
            'ufComprador' => $this->toUpper($record->uf),
        ];

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], 'www.e-crvsp.sp.gov.br')
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/incluirIntencaoVenda.do', $form);

        $body = $response->body();
        $errors = $this->extractErrors($body);

        if (! empty($errors)) {
            $record->update([
                'status' => 'failed',
                'response_errors' => $errors,
            ]);

            return response()->json(
                [
                    'message' => $errors[0],
                    'detalhes' => $errors,
                    'registro_id' => $record->id,
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $messages = $this->extractMessages($body);
        $numeroAtpv = $this->extractNumeroAtpv($messages);

        $parsed = DetranHtmlParser::parse($body);
        $tables = $this->extractTables($body);

        $record->update([
            'status' => 'awaiting_signature',
            'numero_atpv' => $numeroAtpv,
            'response_payload' => [
                'parsed' => $parsed,
                'tables' => $tables,
                'messages' => $messages,
            ],
        ]);

        return response()->json(
            [
                'message' => $messages[0] ?? 'Transação realizada com sucesso.',
                'registro_id' => $record->id,
                'numero_atpv' => $numeroAtpv,
                'status' => 'awaiting_signature',
                'payload' => $parsed,
                'tables' => $tables,
            ],
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractTables(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $tables = [];
        $tableNodes = $xpath->query('//table');

        foreach ($tableNodes as $index => $tableNode) {
            $rows = [];
            $rowNodes = $xpath->query('.//tr', $tableNode);

            foreach ($rowNodes as $rowNode) {
                $cells = $xpath->query('./th|./td', $rowNode);
                if ($cells->length === 0) {
                    continue;
                }

                if ($cells->length === 1) {
                    $value = trim($cells->item(0)?->textContent ?? '');
                    if ($value !== '') {
                        $rows[] = [
                            'label' => null,
                            'value' => $value,
                        ];
                    }
                    continue;
                }

                $label = trim($cells->item(0)?->textContent ?? '');
                $value = trim($cells->item(1)?->textContent ?? '');

                if ($label === '' && $value === '') {
                    continue;
                }

                $rows[] = [
                    'label' => $label !== '' ? preg_replace('/\s+/u', ' ', $label) : null,
                    'value' => $value !== '' ? preg_replace('/\s+/u', ' ', $value) : null,
                ];
            }

            if (! empty($rows)) {
                $tables[] = [
                    'index' => $index,
                    'rows' => $rows,
                ];
            }
        }

        return $tables;
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function splitDocumento(?string $documento): array
    {
        $documento = $documento ? preg_replace('/\D/', '', $documento) : '';
        if (! $documento) {
            return ['0', '', ''];
        }

        if (strlen($documento) > 11) {
            return ['2', '', $this->formatCnpj($documento)];
        }

        return ['1', $this->formatCpf($documento), ''];
    }

    private function formatCpf(string $digits): string
    {
        $digits = str_pad(substr($digits, 0, 11), 11, '0', STR_PAD_LEFT);

        return sprintf(
            '%s.%s.%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3),
            substr($digits, 9, 2)
        );
    }

    private function formatCnpj(string $digits): string
    {
        $digits = str_pad(substr($digits, 0, 14), 14, '0', STR_PAD_LEFT);

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 3),
            substr($digits, 5, 3),
            substr($digits, 8, 4),
            substr($digits, 12, 2)
        );
    }

    private function formatValorVenda(?string $valor): string
    {
        if (! $valor) {
            return '';
        }

        $normalized = str_replace(' ', '', $valor);
        $normalized = str_replace(['R$', 'r$'], '', $normalized);

        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace('.', '', $normalized);
        }

        $number = is_numeric($normalized) ? (float) $normalized : 0.0;

        return number_format($number, 2, ',', '.');
    }

    /**
     * @param array<int, string> $messages
     */
    private function extractNumeroAtpv(array $messages): ?string
    {
        foreach ($messages as $message) {
            if (preg_match('/N[úu]mero\s+ATPV:\s*(\d+)/u', $message, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function toUpper(?string $value): string
    {
        return $value ? mb_strtoupper($value) : '';
    }
}
