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
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .company-info {
            max-width: 65%;
        }

        .company-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
        }

        .company-subtitle {
            font-size: 10px;
            color: #4b5563;
            margin: 0.2rem 0 0;
        }

        .logo {
            width: 90px;
            text-align: right;
        }

        .logo img {
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

        .summary-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }

        .summary-card {
            flex: 1 1 180px;
            min-width: 180px;
            border-radius: 10px;
            padding: 10px 12px;
            border: 1px solid #cbd5f5;
            background: #f8fafc;
        }

        .summary-label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 11px;
            color: #0f172a;
        }

        .section {
            border: 1px solid #cbd5f5;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 14px;
            background: #ffffff;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e40af;
            margin: 0 0 8px;
        }

        .fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .field-cell {
            width: 100%;
        }

        .field-label {
            font-size: 8.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }

        .field-value {
            font-size: 10px;
            color: #1f2937;
            margin-top: 2px;
        }

        .json-block {
            white-space: pre-wrap;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 12px;
            font-size: 9px;
            color: #0f172a;
            margin: 0;
        }
    </style>
</head>
<body>
@php
    $payload = is_array($payload ?? null) ? $payload : [];
    $origin = trim(strtoupper((string) ($origin ?? '')));
    $generatedAt = trim((string) ($generatedAt ?? now()->format('d/m/Y - H:i:s')));
    $consulta = is_array($payload['consulta'] ?? null) ? $payload['consulta'] : [];
    $quantidade = is_array($consulta['quantidade'] ?? null) ? $consulta['quantidade'] : [];
    $fonte = is_array($payload['fonte'] ?? null) ? $payload['fonte'] : [];
    $renajud = is_array($payload['renajud'] ?? null) ? $payload['renajud'] : [];
    $logoPath = public_path('images/logoll.png');

    $formatValue = function ($value, $fallback = '—') {
        if ($value === null) {
            return $fallback;
        }
        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $json === false ? $fallback : $json;
        }
        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }
        $text = trim((string) $value);
        return $text === '' ? $fallback : $text;
    };

    $field = function (string $label, $value) use ($formatValue) {
        return [
            'label' => $label,
            'value' => $formatValue($value),
        ];
    };

    $filterFields = function (array $items) {
        return array_values(array_filter($items, static fn ($item) => ($item['value'] ?? '') !== '—'));
    };

    $summaryFields = $filterFields([
        $field('Origem', $origin),
        $field('Placa', $consulta['placa'] ?? null),
        $field('Município da placa', $consulta['municipio_placa'] ?? null),
        $field('Chassi consultado', $consulta['chassi'] ?? null),
        $field('Ocorrências encontradas', $quantidade['ocorrencias_encontradas'] ?? null),
        $field('Ocorrências exibidas', $quantidade['ocorrencias_exibidas'] ?? null),
    ]);

    $sections = [];

    $consultaSection = $filterFields([
        $field('Origem', $origin),
        $field('Placa', $consulta['placa'] ?? null),
        $field('Município da placa', $consulta['municipio_placa'] ?? null),
        $field('UF da placa', $consulta['uf_placa'] ?? null),
        $field('Chassi', $consulta['chassi'] ?? null),
        $field('Ocorrências encontradas', $quantidade['ocorrencias_encontradas'] ?? null),
        $field('Ocorrências exibidas', $quantidade['ocorrencias_exibidas'] ?? null),
    ]);
    if (!empty($consultaSection)) {
        $sections[] = ['title' => 'Consulta', 'fields' => $consultaSection];
    }

    $fonteSection = $filterFields([
        $field('Título', $fonte['titulo'] ?? null),
        $field('Gerado em', $fonte['gerado_em'] ?? $generatedAt),
    ]);
    if (!empty($fonteSection)) {
        $sections[] = ['title' => 'Fonte', 'fields' => $fonteSection];
    }

    $detalheSection = $filterFields([
        $field('Origem', $consulta['origem'] ?? $origin),
        $field('UF origem', $consulta['uf_origem'] ?? null),
        $field('Placa', $consulta['placa'] ?? null),
        $field('Chassi', $consulta['chassi'] ?? null),
        $field('Município da placa', $consulta['municipio_placa'] ?? null),
        $field('UF da placa', $consulta['uf_placa'] ?? null),
    ]);
    if (!empty($detalheSection)) {
        $sections[] = ['title' => 'Consulta detalhada', 'fields' => $detalheSection];
    }

    foreach ($renajud as $index => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $fields = $filterFields([
            $field('Tipo de bloqueio', $entry['tipo_bloqueio'] ?? $entry['tipo_restricao_judicial'] ?? null),
            $field('Motivo do bloqueio', $entry['motivo_bloqueio'] ?? null),
            $field('Data inclusão', $entry['data_inclusao'] ?? null),
            $field('Hora inclusão', $entry['hora_inclusao'] ?? null),
            $field('Município do bloqueio', $entry['municipio_bloqueio'] ?? null),
            $field('Número protocolo', $entry['numero_protocolo'] ?? null),
            $field('Ano protocolo', $entry['ano_protocolo'] ?? null),
            $field('Número ofício', $entry['numero_oficio'] ?? null),
            $field('Ano ofício', $entry['ano_oficio'] ?? null),
            $field('Número processo', $entry['numero_processo'] ?? null),
            $field('Ano processo', $entry['ano_processo'] ?? null),
            $field('Código tribunal', $entry['codigo_tribunal'] ?? null),
            $field('Órgão judicial', $entry['codigo_orgao_judicial'] ?? null),
            $field('Nome do órgão', $entry['nome_orgao_judicial'] ?? null),
        ]);
        if (!empty($fields)) {
            $sections[] = [
                'title' => sprintf('Bloqueio RENAJUD %s', $index + 1),
                'fields' => $fields,
            ];
        }
    }

    $jsonPayload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }
@endphp

<div class="header">
    <div class="company-info">
        <p class="company-title">LL Despachante</p>
        <p class="company-subtitle">Telefone: (13) 99773-1533</p>
    </div>
    <div class="logo">
        <img src="{{ $logoPath }}" alt="LL Despachante">
    </div>
</div>

<div class="report-title">
    <h2>Bloqueios ativos</h2>
    <p>Data da pesquisa: {{ $generatedAt }}</p>
</div>

<div class="summary-grid">
    @forelse ($summaryFields as $field)
        <div class="summary-card">
            <div class="summary-label">{{ $field['label'] }}</div>
            <div class="summary-value">{{ $field['value'] }}</div>
        </div>
    @empty
        <div class="summary-card">
            <div class="summary-label">Origem</div>
            <div class="summary-value">{{ $origin ?: '—' }}</div>
        </div>
    @endforelse
</div>

@foreach ($sections as $section)
    <div class="section">
        <div class="section-title">{{ $section['title'] }}</div>
        <div class="fields-grid">
            @foreach ($section['fields'] as $field)
                <div class="field-cell">
                    <div class="field-label">{{ $field['label'] }}</div>
                    <div class="field-value">{{ $field['value'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

<div class="section">
    <div class="section-title">Resposta completa</div>
    <pre class="json-block">{{ $jsonPayload }}</pre>
</div>
</body>
</html>
