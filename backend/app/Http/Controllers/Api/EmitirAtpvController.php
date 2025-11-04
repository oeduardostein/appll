<?php

namespace App\Http\Controllers\Api;

use App\Models\AtpvRequest;
use App\Models\User;
use App\Support\DetranHtmlParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EmitirAtpvController extends Controller
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
            'captcha' => ['required', 'string', 'max:12'],
        ]);

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (! $token) {
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a emissão.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
        ]);

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'https://www.e-crvsp.sp.gov.br',
            'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseOutrosEstados.do',
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

        $form = [
            'method' => 'pesquisar',
            'renavam' => $record->renavam,
            'placa' => $record->placa,
            'chassi' => $record->chassi ?? '',
            'hodometro' => $record->hodometro ?? '',
            'email_proprietario' => $record->email_proprietario ?? '',
            'cpf_cnpj_proprietario' => $record->cpf_cnpj_proprietario ?? '',
            'cpf_cnpj_comprador' => $record->cpf_cnpj_comprador ?? '',
            'nome_comprador' => $record->nome_comprador ?? '',
            'email_comprador' => $record->email_comprador ?? '',
            'valor_venda' => $record->valor_venda ?? '',
            'cep_comprador' => $record->cep_comprador ?? '',
            'municipio_comprador' => $record->municipio_comprador ?? '',
            'bairro_comprador' => $record->bairro_comprador ?? '',
            'logradouro_comprador' => $record->logradouro_comprador ?? '',
            'numero_comprador' => $record->numero_comprador ?? '',
            'complemento_comprador' => $record->complemento_comprador ?? '',
            'uf' => $record->uf ?? '',
            'captchaResponse' => strtoupper($data['captcha']),
        ];

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], 'www.e-crvsp.sp.gov.br')
            ->asForm()
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseOutrosEstados.do', $form);

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

        $parsed = DetranHtmlParser::parse($body);
        $tables = $this->extractTables($body);

        $record->update([
            'status' => 'completed',
            'response_payload' => [
                'parsed' => $parsed,
                'tables' => $tables,
            ],
        ]);

        return response()->json(
            [
                'message' => 'ATPV emitida com sucesso.',
                'registro_id' => $record->id,
                'payload' => $parsed,
                'tables' => $tables,
            ],
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    private function findUserFromRequest(Request $request): ?User
    {
        $token = $this->extractTokenFromRequest($request);

        if (! $token) {
            return null;
        }

        return User::where('api_token', hash('sha256', $token))->first();
    }

    private function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');

        if (is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if ($token !== '') {
                return $token;
            }
        }

        $token = $request->input('token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        return null;
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Não autenticado.',
        ], 401);
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
        if (preg_match_all('/errors\[errors\.length\]\s*=\s*[\'"]([^\'"]+)[\'"]\s*;?/u', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $errors[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $errors;
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
}
