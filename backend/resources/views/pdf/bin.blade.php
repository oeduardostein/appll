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
            margin-bottom: 6px;
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
    $normalized = is_array($payload['normalized'] ?? null) ? $payload['normalized'] : [];
    $identificacao = is_array($normalized['identificacao_do_veiculo_na_bin'] ?? null)
        ? $normalized['identificacao_do_veiculo_na_bin']
        : [];
    $gravames = is_array($normalized['gravames'] ?? null) ? $normalized['gravames'] : [];
    $fonte = is_array($payload['fonte'] ?? null) ? $payload['fonte'] : [];
    $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];
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

    $formatLabel = function ($value) {
        $normalized = trim(str_replace('_', ' ', (string) $value));
        if ($normalized === '') {
            return (string) $value;
        }
        return mb_strtoupper(mb_substr($normalized, 0, 1)) . mb_substr($normalized, 1);
    };

    $displayPlaca = $formatValue($identificacao['placa'] ?? $meta['placa'] ?? null);
    $displayRenavam = $formatValue($identificacao['renavam'] ?? $meta['renavam'] ?? null);
    $displayChassi = $formatValue($identificacao['chassi'] ?? $meta['chassi'] ?? null);
    $displayProprietario = $formatValue($gravames['nome_financiado'] ?? null, '');
    $generatedAt = $generatedAt ?? now()->format('d/m/Y - H:i:s');

    $pdfSections = [];
    if (!empty($fonte)) {
        $fields = [];
        foreach ($fonte as $key => $value) {
            $text = $formatValue($value, '');
            if ($text !== '') {
                $fields[] = ['label' => $formatLabel($key), 'value' => $text];
            }
        }
        if (!empty($fields)) {
            $pdfSections[] = [
                'title' => 'Fonte',
                'fields' => $fields,
            ];
        }
    }

    if (!empty($sections)) {
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $title = $section['title'] ?? '';
            $items = $section['items'] ?? [];
            if (!is_string($title) || trim($title) === '') {
                continue;
            }
            if (!is_array($items)) {
                $items = [];
            }
            $fields = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $label = $item['label'] ?? '';
                $value = $item['value'] ?? '';
                $text = $formatValue($value, '');
                if (!is_string($label) || trim($label) === '' || $text === '') {
                    continue;
                }
                $fields[] = ['label' => $label, 'value' => $text];
            }
            if (!empty($fields)) {
                $pdfSections[] = [
                    'title' => $title,
                    'fields' => $fields,
                ];
            }
        }
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
        <h2>PESQUISA BIN</h2>
        <p>Data da pesquisa: {{ $generatedAt }}</p>
    </div>

    <p class="meta-line">Placa: {{ $displayPlaca }} &nbsp; | &nbsp; Renavam: {{ $displayRenavam }} &nbsp; | &nbsp; Chassi: {{ $displayChassi }}</p>
    @if ($displayProprietario !== '')
        <p class="meta-line">Proprietário: {{ $displayProprietario }}</p>
    @endif

    @foreach ($pdfSections as $section)
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
