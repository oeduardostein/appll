<?php

namespace App\Http\Controllers\Api;

use App\Models\AtpvRequest;
use App\Support\CreditValueResolver;
use App\Support\DetranHtmlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmitirAtpvController extends BaseAtpvController
{
    public function __invoke(Request $request): Response
    {
        $user = $this->findUserFromRequest($request);
        $traceId = (string) Str::uuid();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        try {
            $data = $request->validate([
                'method' => ['nullable', 'string', 'max:20'],
                'municipio2' => ['nullable', 'string', 'max:10'],
                'renavam' => ['required', 'string', 'max:20'],
                'placa' => ['required', 'string', 'max:10'],
                'chassi' => ['nullable', 'string', 'max:32'],
                'hodometro' => ['nullable', 'string', 'max:16'],
                'email_proprietario' => ['nullable', 'string', 'max:150'],
                'cpf_cnpj_proprietario' => ['nullable', 'string', 'max:20'],
                'opcao_pesquisa_proprietario' => ['nullable', 'in:1,2'],
                'cpf_cnpj_comprador' => ['required', 'string', 'max:20'],
                'opcao_pesquisa_comprador' => ['required', 'in:1,2'],
                'nome_comprador' => ['required', 'string', 'max:150'],
                'email_comprador' => ['nullable', 'string', 'max:150'],
                'valor_venda' => ['nullable', 'string', 'max:32'],
                'cep_comprador' => ['nullable', 'string', 'max:16'],
                'municipio_comprador' => ['nullable', 'string', 'max:150'],
                'bairro_comprador' => ['nullable', 'string', 'max:150'],
                'logradouro_comprador' => ['nullable', 'string', 'max:150'],
                'numero_comprador' => ['nullable', 'string', 'max:20'],
                'complemento_comprador' => ['nullable', 'string', 'max:100'],
                'uf' => ['required', 'string', 'max:2'],
                'captcha' => ['required', 'string', 'max:12'],
            ]);
        } catch (ValidationException $e) {
            $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($request->all()));
            $fields = $this->addFieldIssues($fields, $e->errors());
            Log::warning('ATPV: validacao falhou', [
                'trace_id' => $traceId,
                'user_id' => $user->id ?? null,
                'fields' => $fields,
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        Log::info('ATPV: inicio emissao', [
            'trace_id' => $traceId,
            'user_id' => $user->id ?? null,
            'fields' => $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data)),
        ]);

        $ownerOption = $data['opcao_pesquisa_proprietario'] ?? null;
        $buyerOption = $data['opcao_pesquisa_comprador'];

        $ownerCpf = '';
        $ownerCnpj = '';
        if ($ownerOption === '1') {
            $ownerCpf = $this->formatCpf($data['cpf_cnpj_proprietario'] ?? '');
            if ($ownerCpf === '') {
                $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data));
                $fields = $this->addFieldIssue($fields, 'cpf_cnpj_proprietario', 'cpf_invalido');
                Log::warning('ATPV: cpf proprietario invalido', [
                    'trace_id' => $traceId,
                    'user_id' => $user->id ?? null,
                    'fields' => $fields,
                ]);
                return $this->validationError('Informe o CPF do proprietário.');
            }
        } elseif ($ownerOption === '2') {
            $ownerCnpj = $this->formatCnpj($data['cpf_cnpj_proprietario'] ?? '');
            if ($ownerCnpj === '') {
                $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data));
                $fields = $this->addFieldIssue($fields, 'cpf_cnpj_proprietario', 'cnpj_invalido');
                Log::warning('ATPV: cnpj proprietario invalido', [
                    'trace_id' => $traceId,
                    'user_id' => $user->id ?? null,
                    'fields' => $fields,
                ]);
                return $this->validationError('Informe o CNPJ do proprietário.');
            }
        }

        $buyerCpf = '';
        $buyerCnpj = '';
        if ($buyerOption === '1') {
            $buyerCpf = $this->formatCpf($data['cpf_cnpj_comprador'] ?? '');
            if ($buyerCpf === '') {
                $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data));
                $fields = $this->addFieldIssue($fields, 'cpf_cnpj_comprador', 'cpf_invalido');
                Log::warning('ATPV: cpf comprador invalido', [
                    'trace_id' => $traceId,
                    'user_id' => $user->id ?? null,
                    'fields' => $fields,
                ]);
                return $this->validationError('Informe o CPF do comprador.');
            }
        } else {
            $buyerCnpj = $this->formatCnpj($data['cpf_cnpj_comprador'] ?? '');
            if ($buyerCnpj === '') {
                $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data));
                $fields = $this->addFieldIssue($fields, 'cpf_cnpj_comprador', 'cnpj_invalido');
                Log::warning('ATPV: cnpj comprador invalido', [
                    'trace_id' => $traceId,
                    'user_id' => $user->id ?? null,
                    'fields' => $fields,
                ]);
                return $this->validationError('Informe o CNPJ do comprador.');
            }
        }

        $token = DB::table('admin_settings')->where('id', 1)->value('value');

        if (! $token) {
            $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data));
            Log::error('ATPV: token ausente para emissao', [
                'trace_id' => $traceId,
                'user_id' => $user->id ?? null,
                'fields' => $fields,
            ]);
            return response()->json(
                ['message' => 'Nenhum token encontrado para realizar a emissão.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $resolver = CreditValueResolver::forUser($user);

        $attributes = [
            'user_id' => $user->id,
            'renavam' => $data['renavam'],
            'placa' => $data['placa'],
            'chassi' => $this->normalizeChassi($data['chassi'] ?? null),
            'hodometro' => $this->normalizeHodometro($data['hodometro'] ?? null),
            'email_proprietario' => $data['email_proprietario'] ?? null,
            'cpf_cnpj_proprietario' => $this->stripNonDigits($data['cpf_cnpj_proprietario'] ?? '') ?: null,
            'cpf_cnpj_comprador' => $this->stripNonDigits($data['cpf_cnpj_comprador']) ?: null,
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
            'credit_value' => $resolver->resolveBySlug('atpv'),
        ];

        if (Schema::hasColumn('atpv_requests', 'opcao_pesquisa_proprietario')) {
            $attributes['opcao_pesquisa_proprietario'] = $ownerOption;
        }

        if (Schema::hasColumn('atpv_requests', 'opcao_pesquisa_comprador')) {
            $attributes['opcao_pesquisa_comprador'] = $buyerOption;
        }

        $record = AtpvRequest::create($attributes);

        Log::info('ATPV: registro criado', [
            'trace_id' => $traceId,
            'atpv_request_id' => $record->id,
            'user_id' => $user->id ?? null,
            'credit_value' => $attributes['credit_value'] ?? null,
            'normalization' => [
                'chassi_changed' => ($data['chassi'] ?? null) !== ($attributes['chassi'] ?? null),
                'hodometro_changed' => ($data['hodometro'] ?? null) !== ($attributes['hodometro'] ?? null),
            ],
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

        $municipioCandidates = $this->buildMunicipioCodeCandidates($data);
        $municipioCode = $municipioCandidates[0] ?? '0';

        Log::info('ATPV: municipio2 candidatos', [
            'trace_id' => $traceId,
            'atpv_request_id' => $record->id,
            'candidates' => $municipioCandidates,
            'selected' => $municipioCode,
            'municipio2_payload' => $this->stripNonDigits($data['municipio2'] ?? '') ?: null,
            'cep_comprador' => $this->maskLogValue('cep_comprador', $data['cep_comprador'] ?? null),
        ]);

        $nomeComprador = $this->sanitizeNomeComprador($record->nome_comprador ?? '');

        $form = [
            'method' => $data['method'] ?? 'pesquisar',
            'municipio2' => $municipioCode,
            'renavam' => $record->renavam,
            'placa' => $record->placa,
            'chassi' => $record->chassi ?? '',
            'hodometro' => $record->hodometro ?? '',
            'emailProprietario' => $record->email_proprietario ?? '',
            'opcaoPesquisaProprietario' => $ownerOption ?? '0',
            'cpfProprietario' => $ownerOption === '1' ? $ownerCpf : '',
            'cnpjProprietario' => $ownerOption === '2' ? $ownerCnpj : '',
            'opcaoPesquisaComprador' => $buyerOption,
            'cpfComprador' => $buyerOption === '1' ? $buyerCpf : '',
            'cnpjComprador' => $buyerOption === '2' ? $buyerCnpj : '',
            'nomeComprador' => $nomeComprador,
            'emailComprador' => $record->email_comprador ?? '',
            'valorVendaSTR' => $this->formatValorVenda($record->valor_venda),
            'cepComprador' => $record->cep_comprador ?? '',
            'municipioComprador' => $this->toUpper($record->municipio_comprador),
            'bairro' => $this->toUpper($record->bairro_comprador),
            'logradouro' => $this->toUpper($record->logradouro_comprador),
            'numeroComprador' => $record->numero_comprador ?? '',
            'complementoComprador' => $record->complemento_comprador ?? '',
            'ufComprador' => $this->toUpper($record->uf),
            'captchaResponse' => strtoupper($data['captcha']),
        ];

        $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data, [
            'municipio2' => $municipioCode,
            'renavam' => $record->renavam,
            'placa' => $record->placa,
            'chassi' => $record->chassi,
            'hodometro' => $record->hodometro,
            'cpf_cnpj_proprietario' => $ownerOption === '1' ? $ownerCpf : $ownerCnpj,
            'cpf_cnpj_comprador' => $buyerOption === '1' ? $buyerCpf : $buyerCnpj,
            'nome_comprador' => $nomeComprador,
            'uf' => $record->uf,
        ]));

        Log::info('ATPV: campos preparados para emissao', [
            'trace_id' => $traceId,
            'atpv_request_id' => $record->id,
            'user_id' => $user->id ?? null,
            'fields' => $fields,
        ]);

        $attempt = $this->submitDetranForm(
            $headers,
            $token,
            $form,
            $record->id,
            $traceId,
            [
                'reason' => 'initial',
                'municipio2' => $municipioCode,
            ]
        );
        $response = $attempt['response'];
        $body = $attempt['body'];
        $errors = $attempt['errors'];
        $messages = $attempt['messages'];

        Log::info('ATPV: resposta detran recebida', [
            'trace_id' => $traceId,
            'atpv_request_id' => $record->id,
            'status' => $response->status(),
            'body_len' => strlen($body),
            'errors' => $errors,
            'messages' => $messages,
            'municipio_attempts' => [$municipioCode],
        ]);

        $municipioAttempts = [$municipioCode];
        if ($this->shouldRetryMunicipioCode($errors, $messages)) {
            $retryCandidates = $municipioCandidates;
            $htmlMunicipioCode = $this->extractMunicipioCodeFromHtml(
                $body,
                $record->municipio_comprador
            );

            if ($htmlMunicipioCode !== null && ! in_array($htmlMunicipioCode, $retryCandidates, true)) {
                // Prioriza o código que o próprio DETRAN expõe no HTML retornado.
                array_splice($retryCandidates, 1, 0, [$htmlMunicipioCode]);
            }

            Log::info('ATPV: fila retry municipio2', [
                'trace_id' => $traceId,
                'atpv_request_id' => $record->id,
                'initial' => $municipioCode,
                'retry_candidates' => $retryCandidates,
                'html_candidate' => $htmlMunicipioCode,
                'municipio_comprador' => $record->municipio_comprador,
            ]);

            foreach ($retryCandidates as $candidate) {
                if (in_array($candidate, $municipioAttempts, true)) {
                    continue;
                }

                $municipioAttempts[] = $candidate;
                $form['municipio2'] = $candidate;

                Log::warning('ATPV: retry com municipio2 alternativo', [
                    'trace_id' => $traceId,
                    'atpv_request_id' => $record->id,
                    'municipio2' => $candidate,
                    'errors' => $errors,
                    'messages' => $messages,
                ]);

                $attempt = $this->submitDetranForm(
                    $headers,
                    $token,
                    $form,
                    $record->id,
                    $traceId,
                    [
                        'reason' => 'municipio2_retry',
                        'municipio2' => $candidate,
                        'attempt' => count($municipioAttempts),
                    ]
                );
                $response = $attempt['response'];
                $body = $attempt['body'];
                $errors = $attempt['errors'];
                $messages = $attempt['messages'];
                $municipioCode = $candidate;

                if (empty($errors) && ! $this->messagesIndicateFailure($messages)) {
                    break;
                }

                if (! $this->shouldRetryMunicipioCode($errors, $messages)) {
                    Log::info('ATPV: retry municipio2 interrompido por mensagem nao relacionada', [
                        'trace_id' => $traceId,
                        'atpv_request_id' => $record->id,
                        'last_municipio2' => $candidate,
                        'messages' => $messages,
                    ]);
                    break;
                }
            }
        }

        $fields = $this->buildAtpvFieldLog($this->collectAtpvFieldValues($data, [
            'municipio2' => $municipioCode,
            'renavam' => $record->renavam,
            'placa' => $record->placa,
            'chassi' => $record->chassi,
            'hodometro' => $record->hodometro,
            'cpf_cnpj_proprietario' => $ownerOption === '1' ? $ownerCpf : $ownerCnpj,
            'cpf_cnpj_comprador' => $buyerOption === '1' ? $buyerCpf : $buyerCnpj,
            'nome_comprador' => $form['nomeComprador'] ?? $nomeComprador,
            'uf' => $record->uf,
        ]));
        $responseFields = $this->mapRemoteIssues($fields, array_merge($errors, $messages));

        if (! empty($errors)) {
            $record->update([
                'status' => 'failed',
                'response_errors' => $errors,
            ]);

            Log::warning('ATPV: detran retornou erros', [
                'trace_id' => $traceId,
                'atpv_request_id' => $record->id,
                'errors' => $errors,
                'messages' => $messages,
                'municipio_attempts' => $municipioAttempts,
                'fields' => $responseFields,
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

        if ($this->messagesIndicateFailure($messages)) {
            $record->update([
                'status' => 'failed',
                'response_errors' => $messages,
            ]);

            Log::warning('ATPV: detran indicou falha', [
                'trace_id' => $traceId,
                'atpv_request_id' => $record->id,
                'messages' => $messages,
                'errors' => $errors,
                'municipio_attempts' => $municipioAttempts,
                'fields' => $responseFields,
            ]);

            return response()->json(
                [
                    'message' => $messages[0] ?? 'Não foi possível emitir a ATPV-e.',
                    'detalhes' => $messages,
                    'registro_id' => $record->id,
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

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

        Log::info('ATPV: emissao concluida', [
            'trace_id' => $traceId,
            'atpv_request_id' => $record->id,
            'numero_atpv' => $numeroAtpv,
            'fields' => $responseFields,
            'messages' => $messages,
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

    private function formatCpf(string $digits): string
    {
        $digits = $this->stripNonDigits($digits);
        if (strlen($digits) !== 11) {
            return '';
        }

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
        $digits = $this->stripNonDigits($digits);
        if (strlen($digits) !== 14) {
            return '';
        }

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

    private function normalizeChassi(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? $normalized;

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeHodometro(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = $this->stripNonDigits($value);

        return $digits !== '' ? $digits : null;
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

    private function sanitizeNomeComprador(string $nome): string
    {
        $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? $nome);
        $cleaned = preg_replace('/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/u', '', $nome) ?? $nome;
        $cleaned = trim(preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned);
        if ($cleaned === '') {
            $cleaned = $nome;
        }
        if (mb_strlen($cleaned) > 80) {
            $cleaned = rtrim(mb_substr($cleaned, 0, 80));
        }
        return $cleaned;
    }

    private function toIso88591(string $value): string
    {
        $converted = @mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
        return $converted !== false ? $converted : $value;
    }

    private function stripAccents(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted === false) {
            return $value;
        }

        $converted = preg_replace('/[^\x20-\x7E]/', '', $converted) ?? $converted;
        return trim(preg_replace('/\s+/u', ' ', $converted) ?? $converted);
    }

    private function shouldRetryNomeComprador(array $errors, array $messages): bool
    {
        $candidates = array_merge($errors, $messages);
        foreach ($candidates as $message) {
            $normalized = mb_strtolower(trim((string) $message));
            if ($normalized === '') {
                continue;
            }
            if (str_contains($normalized, '1101') && str_contains($normalized, 'comprador.nome')) {
                return true;
            }
        }

        return false;
    }

    private function shouldRetryMunicipioCode(array $errors, array $messages): bool
    {
        $candidates = array_merge($errors, $messages);
        foreach ($candidates as $message) {
            $normalized = mb_strtolower(trim((string) $message));
            if ($normalized === '') {
                continue;
            }

            if (str_contains($normalized, '406-') && str_contains($normalized, 'mensagem') && str_contains($normalized, 'cadastrada')) {
                return true;
            }
            if (str_contains($normalized, 'codigomunicipio') && str_contains($normalized, 'inválido')) {
                return true;
            }
            if (str_contains($normalized, 'codigomunicipio') && str_contains($normalized, 'invalido')) {
                return true;
            }
            if (str_contains($normalized, 'comprador.codigomunicipio')) {
                return true;
            }
        }

        return false;
    }

    private function extractMunicipioCodeFromHtml(string $body, ?string $municipioNome): ?string
    {
        $target = $this->normalizeMunicipioName($municipioNome);
        if ($target === '') {
            return null;
        }

        $html = $this->normalizeEncoding($body);
        if ($html === '') {
            return null;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//select[@name="municipio2"]//option[@value]');
        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        foreach ($nodes as $node) {
            $label = trim((string) $node->textContent);
            $value = $this->stripNonDigits((string) $node->attributes?->getNamedItem('value')?->nodeValue);

            if ($label === '' || $value === '') {
                continue;
            }

            if ($this->normalizeMunicipioName($label) === $target) {
                return $value;
            }
        }

        return null;
    }

    private function normalizeMunicipioName(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = mb_strtoupper(trim($value));
        if ($normalized === '') {
            return '';
        }

        $normalized = $this->stripAccents($normalized);
        $normalized = mb_strtoupper($normalized);
        $normalized = preg_replace('/[^A-Z0-9\s]/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function buildMunicipioCodeCandidates(array $data): array
    {
        $candidates = [];

        $payloadCode = $this->stripNonDigits($data['municipio2'] ?? '');
        if ($payloadCode !== '') {
            $candidates[] = $payloadCode;
        }

        if (filled($data['cep_comprador'])) {
            $cepDigits = $this->stripNonDigits($data['cep_comprador']);
            if (strlen($cepDigits) === 8) {
                try {
                    $cepResponse = Http::timeout(5)->get("https://viacep.com.br/ws/{$cepDigits}/json/");
                    if ($cepResponse->ok()) {
                        $cepData = $cepResponse->json();
                        if (is_array($cepData) && !($cepData['erro'] ?? false)) {
                            $ibge = $this->stripNonDigits($cepData['ibge'] ?? '');
                            $siafi = $this->stripNonDigits($cepData['siafi'] ?? '');

                            if ($ibge !== '') {
                                $candidates[] = $ibge;
                            }
                            if ($siafi !== '') {
                                $candidates[] = $siafi;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $candidates[] = '0';

        $unique = [];
        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }
            if (! in_array($candidate, $unique, true)) {
                $unique[] = $candidate;
            }
        }

        return ! empty($unique) ? $unique : ['0'];
    }

    /**
     * @return array{
     *   response:\Illuminate\Http\Client\Response,
     *   body:string,
     *   errors:array<int,string>,
     *   messages:array<int,string>
     * }
     */
    private function submitDetranForm(
        array $headers,
        string $token,
        array $form,
        ?int $atpvRequestId = null,
        ?string $traceId = null,
        array $context = []
    ): array
    {
        $startedAt = microtime(true);
        $response = $this->postDetranForm($headers, $token, $form);
        $body = $response->body();
        $errors = $this->extractErrors($body);
        $messages = $this->extractMessages($body);
        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

        $stats = $response->handlerStats();
        $effectiveUrl = is_array($stats) ? ($stats['url'] ?? null) : null;

        Log::info('ATPV: retorno bruto detran', [
            'trace_id' => $traceId,
            'atpv_request_id' => $atpvRequestId,
            'context' => $context,
            'status' => $response->status(),
            'elapsed_ms' => $elapsedMs,
            'content_type' => $response->header('Content-Type'),
            'effective_url' => $effectiveUrl,
            'errors_count' => count($errors),
            'messages_count' => count($messages),
            'errors' => $errors,
            'messages' => $messages,
            'response_preview' => $this->buildDetranResponsePreview($body),
        ]);

        if ($this->shouldRetryNomeComprador($errors, $messages)) {
            $nomeOriginal = trim((string) ($form['nomeComprador'] ?? ''));
            $nomeSemAcento = $this->stripAccents($nomeOriginal);

            if ($nomeSemAcento !== '' && $nomeSemAcento !== $nomeOriginal) {
                Log::warning('ATPV: retry sem acento no nome do comprador', [
                    'trace_id' => $traceId,
                    'atpv_request_id' => $atpvRequestId,
                    'nome_original' => $nomeOriginal,
                    'nome_sem_acento' => $nomeSemAcento,
                ]);

                $retryStartedAt = microtime(true);
                $form['nomeComprador'] = $nomeSemAcento;
                $response = $this->postDetranForm($headers, $token, $form);
                $body = $response->body();
                $errors = $this->extractErrors($body);
                $messages = $this->extractMessages($body);
                $retryElapsedMs = (int) round((microtime(true) - $retryStartedAt) * 1000);
                $retryStats = $response->handlerStats();
                $retryEffectiveUrl = is_array($retryStats) ? ($retryStats['url'] ?? null) : null;

                Log::info('ATPV: retorno bruto detran apos retry nome', [
                    'trace_id' => $traceId,
                    'atpv_request_id' => $atpvRequestId,
                    'context' => $context,
                    'status' => $response->status(),
                    'elapsed_ms' => $retryElapsedMs,
                    'content_type' => $response->header('Content-Type'),
                    'effective_url' => $retryEffectiveUrl,
                    'errors_count' => count($errors),
                    'messages_count' => count($messages),
                    'errors' => $errors,
                    'messages' => $messages,
                    'response_preview' => $this->buildDetranResponsePreview($body),
                ]);
            }
        }

        return [
            'response' => $response,
            'body' => $body,
            'errors' => $errors,
            'messages' => $messages,
        ];
    }

    private function buildDetranResponsePreview(string $body): string
    {
        $normalized = $this->normalizeEncoding($body);
        $text = strip_tags($normalized);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        return mb_substr($text, 0, 280);
    }

    private function postDetranForm(array $headers, string $token, array $form): \Illuminate\Http\Client\Response
    {
        $formIso = [];
        foreach ($form as $key => $value) {
            $formIso[$key] = $this->toIso88591((string) ($value ?? ''));
        }

        $bodyEncoded = http_build_query($formIso, '', '&', PHP_QUERY_RFC1738);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=ISO-8859-1';

        return Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->withCookies([
                'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                'JSESSIONID' => $token,
            ], 'www.e-crvsp.sp.gov.br')
            ->withBody($bodyEncoded, 'application/x-www-form-urlencoded; charset=ISO-8859-1')
            ->post('https://www.e-crvsp.sp.gov.br/gever/GVR/emissao/incluirIntencaoVenda.do');
    }

    private function stripNonDigits(?string $value): string
    {
        return $value ? preg_replace('/\D/', '', $value) ?? '' : '';
    }

    private function collectAtpvFieldValues(array $data, array $overrides = []): array
    {
        return array_merge([
            'method' => $data['method'] ?? null,
            'municipio2' => $data['municipio2'] ?? null,
            'renavam' => $data['renavam'] ?? null,
            'placa' => $data['placa'] ?? null,
            'chassi' => $data['chassi'] ?? null,
            'hodometro' => $data['hodometro'] ?? null,
            'email_proprietario' => $data['email_proprietario'] ?? null,
            'cpf_cnpj_proprietario' => $data['cpf_cnpj_proprietario'] ?? null,
            'opcao_pesquisa_proprietario' => $data['opcao_pesquisa_proprietario'] ?? null,
            'cpf_cnpj_comprador' => $data['cpf_cnpj_comprador'] ?? null,
            'opcao_pesquisa_comprador' => $data['opcao_pesquisa_comprador'] ?? null,
            'nome_comprador' => $data['nome_comprador'] ?? null,
            'email_comprador' => $data['email_comprador'] ?? null,
            'valor_venda' => $data['valor_venda'] ?? null,
            'cep_comprador' => $data['cep_comprador'] ?? null,
            'municipio_comprador' => $data['municipio_comprador'] ?? null,
            'bairro_comprador' => $data['bairro_comprador'] ?? null,
            'logradouro_comprador' => $data['logradouro_comprador'] ?? null,
            'numero_comprador' => $data['numero_comprador'] ?? null,
            'complemento_comprador' => $data['complemento_comprador'] ?? null,
            'uf' => $data['uf'] ?? null,
            'captcha' => $data['captcha'] ?? null,
        ], $overrides);
    }

    private function buildAtpvFieldLog(array $values): array
    {
        $fields = [];
        foreach ($values as $field => $value) {
            $fields[$field] = [
                'value' => $this->maskLogValue($field, $value),
                'issues' => [],
            ];
        }

        return $fields;
    }

    private function addFieldIssue(array $fields, string $field, string $issue): array
    {
        if (! isset($fields[$field])) {
            $fields[$field] = [
                'value' => null,
                'issues' => [],
            ];
        }

        $fields[$field]['issues'][] = $issue;

        return $fields;
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    private function addFieldIssues(array $fields, array $errors): array
    {
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $fields = $this->addFieldIssue($fields, $field, $message);
            }
        }

        return $fields;
    }

    /**
     * @param array<int, string> $messages
     */
    private function mapRemoteIssues(array $fields, array $messages): array
    {
        $map = [
            'placa' => ['placa'],
            'renavam' => ['renavam'],
            'chassi' => ['chassi'],
            'hodometro' => ['hodometro', 'odometro'],
            'email_proprietario' => ['email proprietario', 'email do proprietario', 'email proprietário', 'email do proprietário'],
            'cpf_cnpj_proprietario' => ['cpf proprietario', 'cpf do proprietario', 'cpf proprietário', 'cpf do proprietário', 'cnpj proprietario', 'cnpj do proprietario', 'cnpj proprietário', 'cnpj do proprietário'],
            'cpf_cnpj_comprador' => ['cpf comprador', 'cpf do comprador', 'cnpj comprador', 'cnpj do comprador'],
            'nome_comprador' => ['nome comprador', 'nome do comprador', 'comprador.nome'],
            'email_comprador' => ['email comprador', 'email do comprador'],
            'valor_venda' => ['valor venda', 'valorvenda', 'valor da venda', 'valor'],
            'cep_comprador' => ['cep'],
            'municipio_comprador' => ['municipio comprador', 'município comprador', 'municipio do comprador', 'município do comprador', 'municipio', 'município'],
            'bairro_comprador' => ['bairro'],
            'logradouro_comprador' => ['logradouro', 'endereco', 'endereço'],
            'numero_comprador' => ['numero', 'número'],
            'complemento_comprador' => ['complemento'],
            'uf' => ['uf'],
            'captcha' => ['captcha', 'captcharesponse'],
            'municipio2' => ['municipio2'],
            'method' => ['method'],
        ];

        foreach ($messages as $message) {
            $normalized = mb_strtolower((string) $message);
            $matched = false;

            foreach ($map as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    if ($keyword !== '' && str_contains($normalized, mb_strtolower($keyword))) {
                        $fields = $this->addFieldIssue($fields, $field, $message);
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (! $matched) {
                $fields = $this->addFieldIssue($fields, '_geral', $message);
            }
        }

        return $fields;
    }

    private function maskLogValue(string $field, $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return '';
        }

        $field = strtolower($field);

        if (str_contains($field, 'cpf_cnpj')) {
            return $this->maskDigits($stringValue, 4);
        }

        if (str_contains($field, 'captcha')) {
            return str_repeat('*', max(0, strlen($stringValue) - 2)) . substr($stringValue, -2);
        }

        if (str_contains($field, 'email')) {
            return $this->maskEmail($stringValue);
        }

        if (str_contains($field, 'chassi')) {
            return $this->maskKeepLast($stringValue, 4);
        }

        return $stringValue;
    }

    private function maskDigits(string $value, int $keepLast): string
    {
        $digits = $this->stripNonDigits($value);
        $len = strlen($digits);
        if ($len <= $keepLast) {
            return $digits;
        }

        return str_repeat('*', $len - $keepLast) . substr($digits, -$keepLast);
    }

    private function maskKeepLast(string $value, int $keepLast): string
    {
        $len = strlen($value);
        if ($len <= $keepLast) {
            return $value;
        }

        return str_repeat('*', $len - $keepLast) . substr($value, -$keepLast);
    }

    private function maskEmail(string $value): string
    {
        $parts = explode('@', $value, 2);
        if (count($parts) !== 2) {
            return $this->maskKeepLast($value, 2);
        }

        $local = $parts[0];
        $domain = $parts[1];
        $visible = substr($local, 0, 2);
        $maskedLocal = $visible . str_repeat('*', max(0, strlen($local) - 2));

        return $maskedLocal . '@' . $domain;
    }

    private function validationError(string $message): Response
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
