<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 32px 28px 36px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #ffffff;
        }

        .header {
            margin-bottom: 16px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 4px;
        }

        .header-subtitle {
            font-size: 10px;
            color: #4b5563;
            margin: 0;
        }

        .header-logo {
            width: 90px;
            text-align: right;
        }

        .header-logo img {
            width: 70px;
            height: auto;
            display: block;
            border-radius: 12px;
            background: #ffffff;
            padding: 6px;
        }

        .report-title {
            text-align: center;
            margin: 12px 0 10px;
        }

        .report-title h2 {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin: 0 0 4px;
        }

        .report-title p {
            margin: 0;
            font-size: 10px;
            color: #4b5563;
        }

        .meta-line {
            font-size: 11px;
            margin-bottom: 14px;
        }

        .section {
            border: 1px solid #cbd5f5;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e40af;
            margin: 0 0 8px;
        }

        .fields {
            width: 100%;
            border-collapse: collapse;
        }

        .field-cell {
            width: 50%;
            padding: 6px 6px 6px 0;
            vertical-align: top;
        }

        .field-label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .field-value {
            font-size: 10px;
            color: #1f2937;
        }
    </style>
</head>
<body>
@php
    $payload = is_array($payload ?? null) ? $payload : [];
    $meta = is_array($meta ?? null) ? $meta : [];
    $veiculo = is_array($payload['veiculo'] ?? null) ? $payload['veiculo'] : [];
    $proprietario = is_array($payload['proprietario'] ?? null) ? $payload['proprietario'] : [];
    $gravames = is_array($payload['gravames'] ?? null) ? $payload['gravames'] : [];
    $gravamesDatas = is_array($gravames['datas'] ?? null) ? $gravames['datas'] : [];
    $intencaoGravame = is_array($payload['intencao_gravame'] ?? null) ? $payload['intencao_gravame'] : [];
    $debitosMultas = is_array($payload['debitos_multas'] ?? null) ? $payload['debitos_multas'] : [];
    $restricoes = is_array($payload['restricoes'] ?? null) ? $payload['restricoes'] : [];
    $crvCrlvAtualizacao = is_array($payload['crv_crlv_atualizacao'] ?? null) ? $payload['crv_crlv_atualizacao'] : [];
    $comunicacaoVendas = is_array($payload['comunicacao_vendas'] ?? null) ? $payload['comunicacao_vendas'] : [];
    $comunicacaoVendasDatas = is_array($comunicacaoVendas['datas'] ?? null) ? $comunicacaoVendas['datas'] : [];
    $logoPath = public_path('images/logoll.png');

    $formatValue = function ($value, $fallback = '—') {
        if ($value === null) {
            return $fallback;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? $fallback : $trimmed;
        }
        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }
        return (string) $value;
    };

    $field = function ($label, $value, $currency = false) use ($formatValue) {
        $text = $formatValue($value, null);
        if ($text === null) {
            return null;
        }
        if ($currency) {
            $text = 'R$ ' . $text;
        }
        return ['label' => $label, 'value' => $text];
    };

    $sections = [
        [
            'title' => 'Identificação do veículo',
            'fields' => array_filter([
                $field('Proprietário', $proprietario['nome'] ?? null),
                $field(
                    'CPF/CNPJ',
                    $proprietario['cpf_cnpj'] ?? $proprietario['documento'] ?? $proprietario['cpf'] ?? $proprietario['cnpj'] ?? null
                ),
                $field('Placa', $veiculo['placa'] ?? null),
                $field('Renavam', $veiculo['renavam'] ?? null),
                $field('Chassi', $veiculo['chassi'] ?? null),
                $field('Município', $veiculo['municipio'] ?? null),
                $field('UF', $veiculo['uf'] ?? null),
                $field('Data da inclusão', $veiculo['data_inclusao'] ?? null),
                $field('Exercício licenciamento', $crvCrlvAtualizacao['exercicio_licenciamento'] ?? null),
                $field('Data licenciamento', $crvCrlvAtualizacao['data_licenciamento'] ?? null),
                $field('Data emissão CRV', $veiculo['data_emissao_crv'] ?? null),
                $field('Número do motor', $veiculo['numero_motor'] ?? null),
                $field('Proprietário anterior', $veiculo['proprietario_anterior'] ?? null),
            ]),
        ],
        [
            'title' => 'Características do veículo',
            'fields' => array_filter([
                $field('Marca/Modelo', $veiculo['marca'] ?? null),
                $field('Ano fabricação', $veiculo['ano_fabricacao'] ?? null),
                $field('Ano modelo', $veiculo['ano_modelo'] ?? null),
                $field('Tipo', $veiculo['tipo'] ?? null),
                $field('Cor', $veiculo['cor'] ?? null),
                $field('Procedência', $veiculo['procedencia'] ?? null),
                $field('Categoria', $veiculo['categoria'] ?? null),
                $field('Combustível', $veiculo['combustivel'] ?? null),
                $field('Capacidade', $veiculo['capacidade'] ?? null),
                $field('Cilindrada', $veiculo['cilindrada'] ?? null),
                $field('Potência', $veiculo['potencia'] ?? null),
                $field('Capac. carga', $veiculo['capacidade_carga'] ?? null),
            ]),
        ],
        [
            'title' => 'Gravame',
            'fields' => array_filter([
                $field('Restrição financeira', $gravames['restricao_financeira'] ?? null),
                $field('Agente financeiro', $gravames['nome_agente'] ?? null),
                $field('Arrendatário/Financiado', $gravames['arrendatario'] ?? null),
                $field('CPF/CNPJ financiado', $gravames['cnpj_cpf_financiado'] ?? null),
                $field('Inclusão financiamento', $gravamesDatas['inclusao_financiamento'] ?? null),
                $field('Agente intenção', $intencaoGravame['agente_financeiro'] ?? null),
                $field('Nome financiado', $intencaoGravame['nome_financiado'] ?? null),
                $field('CPF/CNPJ intenção', $intencaoGravame['cnpj_cpf'] ?? null),
                $field('Data inclusão intenção', $intencaoGravame['data_inclusao'] ?? null),
            ]),
        ],
        [
            'title' => 'Restrições / Bloqueios',
            'fields' => array_filter([
                $field('Furto', $restricoes['furto'] ?? null),
                $field('Guincho', $restricoes['bloqueio_guincho'] ?? null),
                $field('Administrativas', $restricoes['administrativas'] ?? null),
                $field('Judicial', $restricoes['judicial'] ?? null),
                $field('Tributária', $restricoes['tributaria'] ?? null),
                $field('RENAJUD', $restricoes['renajud'] ?? null),
                $field('Inspeção ambiental', $restricoes['inspecao_ambiental'] ?? null),
            ]),
        ],
        [
            'title' => 'Multas e débitos',
            'fields' => array_filter([
                $field('DER', $debitosMultas['der'] ?? null, true),
                $field('DETRAN', $debitosMultas['detran'] ?? null, true),
                $field('DERSA', $debitosMultas['dersa'] ?? null, true),
                $field('CETESB', $debitosMultas['cetesb'] ?? null, true),
                $field('RENAINF', $debitosMultas['renainf'] ?? null, true),
                $field('Municipais', $debitosMultas['municipais'] ?? null, true),
                $field('IPVA', $debitosMultas['ipva'] ?? null, true),
                $field('Polícia Rod. Fed.', $debitosMultas['prf'] ?? null, true),
            ]),
        ],
        [
            'title' => 'Comunicação de venda',
            'fields' => array_filter([
                $field('Status', $comunicacaoVendas['status'] ?? null),
                $field('Inclusão', $comunicacaoVendas['inclusao'] ?? null),
                $field('Tipo doc. comprador', $comunicacaoVendas['tipo_doc_comprador'] ?? null),
                $field('CPF/CNPJ comprador', $comunicacaoVendas['cnpj_cpf_comprador'] ?? null),
                $field('Origem', $comunicacaoVendas['origem'] ?? null),
                $field('Data venda', $comunicacaoVendasDatas['venda'] ?? null),
                $field('Nota fiscal', $comunicacaoVendasDatas['nota_fiscal'] ?? null),
                $field('Protocolo Detran', $comunicacaoVendasDatas['protocolo_detran'] ?? null),
            ]),
        ],
    ];

    $sections = array_values(array_filter($sections, fn ($section) => !empty($section['fields'])));
    $chassi = $formatValue($meta['chassi'] ?? $veiculo['chassi'] ?? null);
    $uf = $formatValue($meta['uf'] ?? $veiculo['uf'] ?? null);
    $generatedAt = $generatedAt ?? now()->format('d/m/Y - H:i:s');
@endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <p class="header-title">LL DESPACHANTE</p>
                    <p class="header-subtitle">Telefone: (13) 99773-1533</p>
                </td>
                <td class="header-logo">
                    <img src="{{ $logoPath }}" alt="Logo LL">
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title">
        <h2>PESQUISA BASE OUTROS ESTADOS</h2>
        <p>Data da pesquisa: {{ $generatedAt }}</p>
    </div>

    <p class="meta-line">Chassi: {{ $chassi }} &nbsp; | &nbsp; UF: {{ $uf }}</p>

    @foreach ($sections as $section)
        <div class="section">
            <div class="section-title">{{ $section['title'] }}</div>
            <table class="fields">
                @foreach (array_chunk($section['fields'], 2) as $row)
                    <tr>
                        @foreach ($row as $field)
                            <td class="field-cell">
                                <div class="field-label">{{ $field['label'] }}</div>
                                <div class="field-value">{{ $field['value'] }}</div>
                            </td>
                        @endforeach
                        @if (count($row) < 2)
                            <td class="field-cell"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>
