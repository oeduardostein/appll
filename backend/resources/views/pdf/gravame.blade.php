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
    $gravames = is_array($payload['gravames'] ?? null) ? $payload['gravames'] : [];
    $gravamesDatas = is_array($payload['gravames_datas'] ?? null) ? $payload['gravames_datas'] : [];
    $intencao = is_array($payload['intencao_gravame'] ?? null) ? $payload['intencao_gravame'] : [];
    $fonte = is_array($payload['fonte'] ?? null) ? $payload['fonte'] : [];
    $origin = trim((string) ($payload['origin'] ?? ''));
    $generatedAt = $generatedAt ?? now()->format('d/m/Y - H:i:s');
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

    $field = function ($label, $value) use ($formatValue) {
        return ['label' => $label, 'value' => $formatValue($value)];
    };

    $filterFields = function (array $items) {
        return array_values(array_filter($items, static fn ($item) => ($item['value'] ?? '') !== '—'));
    };

    $originLabel = $origin === 'another_base_estadual' ? 'Outros estados' : 'Base estadual';

    $sections = [];

    $consultaFields = $filterFields([
        $field('Placa', $meta['placa'] ?? $veiculo['placa'] ?? null),
        $field('Renavam', $meta['renavam'] ?? $veiculo['renavam'] ?? null),
        $field('UF', $meta['uf'] ?? $veiculo['uf'] ?? null),
        $field('Origem', $originLabel),
        $field('Gerado em', $generatedAt),
    ]);

    if (!empty($consultaFields)) {
        $sections[] = ['title' => 'Dados da consulta', 'fields' => $consultaFields];
    }

    $veiculoFields = $filterFields([
        $field('Marca', $veiculo['marca'] ?? null),
        $field('Modelo', $veiculo['modelo'] ?? null),
        $field('Tipo', $veiculo['tipo'] ?? null),
        $field('Categoria', $veiculo['categoria'] ?? null),
        $field('Procedência', $veiculo['procedencia'] ?? null),
        $field('Combustível', $veiculo['combustivel'] ?? null),
        $field('Cor', $veiculo['cor'] ?? null),
        $field('Ano fabricação', $veiculo['ano_fabricacao'] ?? null),
        $field('Ano modelo', $veiculo['ano_modelo'] ?? null),
        $field('Chassi', $veiculo['chassi'] ?? null),
    ]);

    if (!empty($veiculoFields)) {
        $sections[] = ['title' => 'Veículo', 'fields' => $veiculoFields];
    }

    $gravameFields = $filterFields([
        $field('Restrição financeira', $gravames['restricao_financeira'] ?? null),
        $field('Nome do agente', $gravames['nome_agente'] ?? null),
        $field('Arrendatário', $gravames['arrendatario'] ?? null),
        $field('CNPJ/CPF financiado', $gravames['cnpj_cpf_financiado'] ?? null),
        $field('Número do contrato', $gravames['numero_contrato'] ?? null),
    ]);

    if (!empty($gravameFields)) {
        $sections[] = ['title' => 'Gravame atual', 'fields' => $gravameFields];
    }

    $gravameDatasFields = $filterFields([
        $field('Inclusão financiamento', $gravamesDatas['inclusao_financiamento'] ?? null),
        $field('Emissão', $gravamesDatas['emissao'] ?? null),
    ]);

    if (!empty($gravameDatasFields)) {
        $sections[] = ['title' => 'Gravame - Datas', 'fields' => $gravameDatasFields];
    }

    $intencaoFields = $filterFields([
        $field('Restrição financeira', $intencao['restricao_financeira'] ?? null),
        $field('Agente financeiro', $intencao['agente_financeiro'] ?? null),
        $field('Nome financiado', $intencao['nome_financiado'] ?? null),
        $field('CNPJ/CPF', $intencao['cnpj_cpf'] ?? null),
        $field('Data inclusão', $intencao['data_inclusao'] ?? null),
    ]);

    if (!empty($intencaoFields)) {
        $sections[] = ['title' => 'Intenção de gravame', 'fields' => $intencaoFields];
    }
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
    <h2>PESQUISA GRAVAME</h2>
    <p>Data da pesquisa: {{ $generatedAt }}</p>
</div>

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
