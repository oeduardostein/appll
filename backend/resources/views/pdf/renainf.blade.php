<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 28px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; background: #fff; }
        .header { margin-bottom: 16px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-title { font-size: 18px; font-weight: bold; color: #1e3a8a; margin: 0 0 4px; }
        .header-subtitle { font-size: 10px; color: #4b5563; margin: 0; }
        .header-logo { width: 90px; text-align: right; }
        .header-logo img { width: 70px; height: auto; display: block; border-radius: 12px; background: #fff; padding: 6px; }
        .report-title { text-align: center; margin: 12px 0 10px; }
        .report-title h2 { font-size: 14px; font-weight: bold; color: #1e40af; margin: 0 0 4px; }
        .report-title p { margin: 0; font-size: 10px; color: #4b5563; }
        .meta-line { font-size: 11px; margin-bottom: 14px; }
        .section { border: 1px solid #cbd5f5; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1e40af; margin: 0 0 8px; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; }
        .col { flex: 1; min-width: 120px; }
        .label { font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
        .value { font-size: 12px; font-weight: 600; color: #1f2937; }
        .item { margin-bottom: 10px; }
        .item small { font-size: 9px; color: #475467; }
    </style>
</head>
<body>
@php
    $payload = is_array($payload ?? null) ? $payload : [];
    $meta = is_array($meta ?? null) ? $meta : [];
    $summary = $payload['summary'] ?? $payload['resumo'] ?? $payload['totals'] ?? [];
    $renainf = is_array($payload['renainf'] ?? null) ? $payload['renainf'] : [];
    $occurrences = [];
    if (!empty($payload['occurrences']) && is_array($payload['occurrences'])) {
        $occurrences = $payload['occurrences'];
    } elseif (!empty($renainf['ocorrencias']) && is_array($renainf['ocorrencias'])) {
        $occurrences = $renainf['ocorrencias'];
    }
    $infractions = [];
    $infractionsCandidates = ['infractions', 'infracoes', 'items', 'itens', 'infractions_list', 'resultado'];
    foreach ($infractionsCandidates as $candidate) {
        if (!empty($payload[$candidate]) && is_array($payload[$candidate])) {
            $infractions = $payload[$candidate];
            break;
        }
    }
    $consulta = is_array($payload['consulta'] ?? null) ? $payload['consulta'] : [];
    $plate = trim((string) ($payload['plate'] ?? $meta['plate'] ?? '—')) ?: '—';
    $statusLabel = trim((string) ($payload['statusLabel'] ?? $payload['status_label'] ?? $payload['status'] ?? $meta['statusLabel'] ?? '—')) ?: '—';
    $uf = trim((string) ($payload['uf'] ?? $meta['uf'] ?? '—')) ?: '—';
    $startDate = trim((string) ($meta['startDate'] ?? $payload['start_date'] ?? ''));
    $endDate = trim((string) ($meta['endDate'] ?? $payload['end_date'] ?? ''));
    $generatedAt = $generatedAt ?? now()->format('d/m/Y - H:i:s');
    $sourceTitle = trim((string) ($payload['sourceTitle'] ?? $payload['fonte']['titulo'] ?? ''));
    $sourceGenerated = trim((string) ($payload['sourceGeneratedAt'] ?? $payload['fonte']['gerado_em'] ?? ''));

    $formatCurrency = function ($value) {
        if ($value === null || $value === '') return '—';
        $normalized = preg_replace('/[^0-9,\.\-]/', '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);
        if ($normalized === '') return '—';
        return 'R$ ' . number_format((float) $normalized, 2, ',', '.');
    };

    $formatDate = function ($value) {
        if (!$value) return '—';
        if (strpos($value, '/') !== false) return $value;
        $parts = explode('-', $value);
        if (count($parts) === 3) {
            return sprintf('%s/%s/%s', $parts[2], $parts[1], $parts[0]);
        }
        return $value;
    };

    $totalInfractions = $summary['totalInfractions'] ?? $summary['total_infractions'] ?? $summary['total'] ?? count($infractions);
    $totalValue = $summary['totalValue'] ?? $summary['total_value'] ?? $summary['valor_total'] ?? $summary['total'] ?? 0;
    $openValue = $summary['openValue'] ?? $summary['open_value'] ?? $summary['valor_em_aberto'] ?? 0;
    $lastUpdated = trim((string) ($summary['lastUpdatedLabel'] ?? $summary['last_updated_at'] ?? $sourceGenerated ?? '—')) ?: '—';
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td>
                <p class="header-title">LL DESPACHANTE</p>
                <p class="header-subtitle">Telefone: (13) 99773-1533</p>
            </td>
            <td class="header-logo">
                <img src="{{ public_path('images/logoll.png') }}" alt="Logo LL">
            </td>
        </tr>
    </table>
</div>

<div class="report-title">
    <h2>CONSULTA RENAINF</h2>
    <p>Data da pesquisa: {{ $generatedAt }}</p>
</div>

<div class="section">
    <div class="section-title">Resumo da consulta</div>
    <div class="row">
        <div class="col">
            <div class="label">Placa</div>
            <div class="value">{{ $plate }}</div>
        </div>
        <div class="col">
            <div class="label">Status da multa</div>
            <div class="value">{{ $statusLabel }}</div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="label">UF pesquisada</div>
            <div class="value">{{ $uf }}</div>
        </div>
        <div class="col">
            <div class="label">Período</div>
            <div class="value">{{ $formatDate($startDate) }} • {{ $formatDate($endDate) }}</div>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">Resumo financeiro</div>
    <div class="row">
        <div class="col">
            <div class="label">Total de infrações</div>
            <div class="value">{{ $totalInfractions }}</div>
        </div>
        <div class="col">
            <div class="label">Valor total</div>
            <div class="value">{{ $formatCurrency($totalValue) }}</div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="label">Valor em aberto</div>
            <div class="value">{{ $formatCurrency($openValue) }}</div>
        </div>
        <div class="col">
            <div class="label">Última atualização</div>
            <div class="value">{{ $lastUpdated }}</div>
        </div>
    </div>
</div>

@if (!empty($consulta))
    <div class="section">
        <div class="section-title">Dados da consulta</div>
        <div class="row">
            @if (!empty($consulta['placa']))
                <div class="col">
                    <div class="label">Placa consultada</div>
                    <div class="value">{{ $consulta['placa'] }}</div>
                </div>
            @endif
            @if (!empty($consulta['uf_emplacamento']))
                <div class="col">
                    <div class="label">UF de emplacamento</div>
                    <div class="value">{{ $consulta['uf_emplacamento'] }}</div>
                </div>
            @endif
        </div>
        <div class="row">
            @if (!empty($consulta['indicador_exigibilidade']))
                <div class="col">
                    <div class="label">Indicador de exigibilidade</div>
                    <div class="value">{{ $consulta['indicador_exigibilidade'] }}</div>
                </div>
            @endif
        </div>
    </div>
@endif

@if ($sourceTitle || $sourceGenerated)
    <div class="section">
        <div class="section-title">Fonte</div>
        @if ($sourceTitle)
            <div class="item">
                <div class="label">Sistema</div>
                <div class="value">{{ $sourceTitle }}</div>
            </div>
        @endif
        @if ($sourceGenerated)
            <div class="item">
                <div class="label">Gerado em</div>
                <div class="value">{{ $sourceGenerated }}</div>
            </div>
        @endif
    </div>
@endif

@if (!empty($occurrences))
    <div class="section">
        <div class="section-title">Ocorrências</div>
        @foreach ($occurrences as $occur)
            <div class="item">
                <div class="row">
                    <div class="col">
                        <div class="label">Orgão autuador</div>
                        <div class="value">{{ $occur['orgao_autuador'] ?? $occur['orgao'] ?? '—' }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Auto de infração</div>
                        <div class="value">{{ $occur['auto_infracao'] ?? $occur['auto'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="label">Infração</div>
                        <div class="value">{{ $occur['infracao'] ?? $occur['codigo_infracao'] ?? '—' }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Data</div>
                        <div class="value">{{ $occur['data_infracao'] ?? $occur['data'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="label">Exigibilidade</div>
                        <div class="value">{{ $occur['exigibilidade'] ?? $occur['indicador_exigibilidade'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if (!empty($infractions))
    <div class="section">
        <div class="section-title">Infrações</div>
        @foreach ($infractions as $infraction)
            <div class="item">
                <div class="row">
                    <div class="col">
                        <div class="label">Auto</div>
                        <div class="value">{{ $infraction['auto_infracao'] ?? $infraction['code'] ?? '—' }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Descrição</div>
                        <div class="value">{{ $infraction['description'] ?? $infraction['descricao'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="label">Valor</div>
                        <div class="value">{{ $formatCurrency($infraction['amount'] ?? $infraction['valor'] ?? $infraction['valor_infracao'] ?? $infraction['valorMulta']) }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Status</div>
                        <div class="value">{{ $infraction['status'] ?? $infraction['situacao'] ?? '—' }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Data</div>
                        <div class="value">{{ $infraction['data'] ?? $infraction['data_emissao'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="section">
    <div class="section-title">Dados brutos</div>
    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
</div>
</body>
</html>
