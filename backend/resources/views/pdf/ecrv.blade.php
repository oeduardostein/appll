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

        .section-empty {
            font-size: 10px;
            color: #4b5563;
            border-radius: 6px;
            padding: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
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
    $placa = trim((string) ($placa ?? ''));
    $numeroFicha = trim((string) ($numeroFicha ?? ''));
    $anoFicha = trim((string) ($anoFicha ?? ''));
    $status = trim((string) ($status ?? ''));
    $retornoConsistencia = trim((string) ($retornoConsistencia ?? ''));
    $fichaData = is_array($fichaData ?? null) ? $fichaData : [];
    $andamentoInfo = is_array($andamentoInfo ?? null) ? $andamentoInfo : [];
    $datasInfo = is_array($datasInfo ?? null) ? $datasInfo : [];
    $anexosInfo = is_array($anexosInfo ?? null) ? $anexosInfo : [];
    $generatedAt = trim((string) ($generatedAt ?? now()->format('d/m/Y - H:i:s')));
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

    $formatLabel = function ($key) {
        $parts = preg_split('/[_\s]+/', (string) $key);
        $parts = array_filter($parts, static fn ($segment) => $segment !== '');
        $formatted = array_map(static fn ($segment) => ucfirst(mb_strtolower($segment, 'UTF-8')), $parts);
        return $formatted ? implode(' ', $formatted) : ucfirst((string) $key);
    };

    $buildFields = function (array $source) use ($formatValue, $formatLabel) {
        $rows = [];
        foreach ($source as $key => $value) {
            $formatted = $formatValue($value);
            if ($formatted === '—') {
                continue;
            }
            $rows[] = [
                'label' => $formatLabel($key),
                'value' => $formatted,
            ];
        }
        return $rows;
    };

    $jsonPayload = json_encode($responsePayload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        $jsonPayload = json_encode($responsePayload ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    $summaryFields = [
        ['label' => 'Placa', 'value' => $placa !== '' ? $placa : '—'],
        ['label' => 'Número da ficha', 'value' => $numeroFicha !== '' ? $numeroFicha : '—'],
        ['label' => 'Ano da ficha', 'value' => $anoFicha !== '' ? $anoFicha : '—'],
        ['label' => 'Status registrado', 'value' => $status !== '' ? $status : '—'],
    ];
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
    <h2>Andamento do processo e-CRV</h2>
    <p>Data da pesquisa: {{ $generatedAt }}</p>
</div>

<div class="summary-grid">
    @foreach ($summaryFields as $field)
        <div class="summary-card">
            <div class="summary-label">{{ $field['label'] }}</div>
            <div class="summary-value">{{ $field['value'] }}</div>
        </div>
    @endforeach
</div>

<div class="section">
    <div class="section-title">Ficha cadastral</div>
    @php
        $fichaRows = $buildFields($fichaData);
    @endphp
    @if (!empty($fichaRows))
        <div class="fields-grid">
            @foreach ($fichaRows as $field)
                <div class="field-cell">
                    <div class="field-label">{{ $field['label'] }}</div>
                    <div class="field-value">{{ $field['value'] }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div class="section-empty">Nenhuma informação da ficha disponível.</div>
    @endif
</div>

<div class="section">
    <div class="section-title">Andamento do processo</div>
    @php $andamentoRows = $buildFields($andamentoInfo); @endphp
    @if (!empty($andamentoRows))
        <div class="fields-grid">
            @foreach ($andamentoRows as $field)
                <div class="field-cell">
                    <div class="field-label">{{ $field['label'] }}</div>
                    <div class="field-value">{{ $field['value'] }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div class="section-empty">Nenhum dado de andamento disponível.</div>
    @endif
</div>

<div class="section">
    <div class="section-title">Datas</div>
    @php $datasRows = $buildFields($datasInfo); @endphp
    @if (!empty($datasRows))
        <div class="fields-grid">
            @foreach ($datasRows as $field)
                <div class="field-cell">
                    <div class="field-label">{{ $field['label'] }}</div>
                    <div class="field-value">{{ $field['value'] }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div class="section-empty">Nenhuma data registrada.</div>
    @endif
</div>

<div class="section">
    <div class="section-title">Documentos anexos</div>
    @php $anexosRows = $buildFields($anexosInfo); @endphp
    @if (!empty($anexosRows))
        <div class="fields-grid">
            @foreach ($anexosRows as $field)
                <div class="field-cell">
                    <div class="field-label">{{ $field['label'] }}</div>
                    <div class="field-value">{{ $field['value'] }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div class="section-empty">Nenhum documento anexado.</div>
    @endif
</div>

<div class="section">
    <div class="section-title">Resposta completa</div>
    <pre class="json-block">{{ $jsonPayload }}</pre>
</div>
</body>
</html>
