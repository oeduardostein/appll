<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 28px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: #f5f6fb;
        }

        .layout {
            width: 100%;
            display: block;
        }

        .header-card {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #ffffff;
            border-radius: 18px;
            padding: 20px 28px;
            margin-bottom: 18px;
        }

        .header-card .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 6px;
        }

        .header-card .subtitle {
            font-size: 12px;
            opacity: 0.85;
            margin-bottom: 12px;
        }

        .header-card .plate {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 0;
        }

        .columns {
            width: 100%;
            display: table;
            margin-bottom: 14px;
        }

        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .column:last-child {
            padding-right: 0;
            padding-left: 10px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 22px;
            box-shadow: 0 9px 24px rgba(30, 64, 175, 0.15);
            margin-bottom: 18px;
        }

        .card-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e3a8a;
            margin: 0 0 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .info-grid {
            width: 100%;
            border-spacing: 0;
        }

        .info-grid tr td {
            padding: 6px 0;
            vertical-align: top;
        }

        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.6px;
        }

        .info-value {
            font-size: 12px;
            color: #1f2937;
            font-weight: 600;
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.16);
            color: #1d4ed8;
            margin-top: 8px;
        }

        .footer-note {
            font-size: 10px;
            color: #6b7280;
            text-align: center;
            margin-top: 24px;
        }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 14px 0;
        }

        .highlight {
            color: #047857;
        }

        .warning {
            color: #b91c1c;
        }
    </style>
</head>
<body>
<div class="layout">
    @php
        $veiculo = $dados['veiculo'] ?? [];
        $proprietario = $dados['proprietario']['nome'] ?? $usuario;
        $atualizacao = $dados['crv_crlv_atualizacao'] ?? [];
        $status = $metadata['status_texto'] ?? null;
        $statusClass = $status ? (stripos($status, 'nada consta') !== false ? 'highlight' : 'warning') : '';
        $safe = fn($value, $fallback = '-') => $value !== null && $value !== '' ? $value : $fallback;
    @endphp

    <div class="header-card">
        <p class="title">CRLV-e Digital</p>
        <p class="subtitle">
            Emitido em {{ $metadata['emitido_em'] ?? '-' }}
            @if(!empty($metadata['documento']))
                • Doc: {{ $metadata['documento'] }}
            @endif
        </p>
        <p class="plate">{{ $safe($veiculo['placa'] ?? null, 'PLACA') }}</p>
        @if ($proprietario)
            <p style="margin: 6px 0 0; font-size: 13px;">Proprietário: <strong>{{ $proprietario }}</strong></p>
        @endif
        @if ($status)
            <span class="badge {{ $statusClass }}">{{ $status }}</span>
        @endif
    </div>

    <div class="columns">
        <div class="column">
            <div class="card">
                <p class="card-title">Identificação do Veículo</p>
                <table class="info-grid">
                    <tr>
                        <td>
                            <div class="info-label">RENAVAM</div>
                            <div class="info-value">{{ $safe($veiculo['renavam'] ?? null) }}</div>
                        </td>
                        <td>
                            <div class="info-label">CHASSI</div>
                            <div class="info-value">{{ $safe($veiculo['chassi'] ?? null) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">MARCA / MODELO</div>
                            <div class="info-value">{{ $safe($veiculo['marca'] ?? null) }}</div>
                        </td>
                        <td>
                            <div class="info-label">COR</div>
                            <div class="info-value">{{ $safe($veiculo['cor'] ?? null) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">ANOS</div>
                            <div class="info-value">
                                Fab. {{ $safe($veiculo['ano_fabricacao'] ?? null) }} • Mod. {{ $safe($veiculo['ano_modelo'] ?? null) }}
                            </div>
                        </td>
                        <td>
                            <div class="info-label">COMBUSTÍVEL</div>
                            <div class="info-value">{{ $safe($veiculo['combustivel'] ?? null) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">TIPO</div>
                            <div class="info-value">{{ $safe($veiculo['tipo'] ?? null) }}</div>
                        </td>
                        <td>
                            <div class="info-label">CATEGORIA</div>
                            <div class="info-value">{{ $safe($veiculo['categoria'] ?? null) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">PROCEDÊNCIA</div>
                            <div class="info-value">{{ $safe($veiculo['procedencia'] ?? null) }}</div>
                        </td>
                        <td>
                            <div class="info-label">MUNICÍPIO</div>
                            <div class="info-value">{{ $safe($veiculo['municipio'] ?? null) }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="column">
            <div class="card">
                <p class="card-title">Licenciamento</p>
                <table class="info-grid">
                    <tr>
                        <td>
                            <div class="info-label">EXERCÍCIO</div>
                            <div class="info-value">{{ $safe($atualizacao['exercicio_licenciamento'] ?? null) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">LICENCIAMENTO</div>
                            <div class="info-value">{{ $safe($atualizacao['data_licenciamento'] ?? null) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">SITUAÇÃO</div>
                            <div class="info-value {{ $statusClass }}">{{ $safe($status) }}</div>
                        </td>
                    </tr>
                </table>
                <div class="divider"></div>
                <p class="card-title">Fonte da Consulta</p>
                <div class="info-label">SISTEMA</div>
                <div class="info-value">{{ $safe($dados['fonte']['titulo'] ?? null, 'eCRVsp - DETRAN/SP') }}</div>
                <div class="info-label" style="margin-top: 10px;">PROTOCOLO</div>
                <div class="info-value">Gerado automaticamente pelo app APPLL</div>
            </div>
        </div>
    </div>

    <p class="footer-note">
        Documento válido como comprovante digital de licenciamento. Consulte o DETRAN/SP para confirmar autenticidade.
    </p>
</div>
</body>
</html>
