<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 40px;
            color: #1f2937;
            font-size: 12px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 20px;
            color: #0b4ea2;
        }

        .subtitle {
            margin: 0 0 16px;
            color: #6b7280;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
        }

        .summary-card strong {
            display: block;
            font-size: 16px;
            margin-bottom: 4px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th,
        td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f3f4f6;
            font-size: 12px;
            color: #6b7280;
        }

        tfoot td {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #d1d5db;
        }
    </style>
</head>

<body>
    <h1>Relatório de créditos</h1>
    <p class="subtitle">
        Cliente {{ $user->name }} — {{ $selectedMonthLabel }}
    </p>

    <div class="summary-grid">
        @foreach ($creditSummary as $card)
            <div class="summary-card">
                <strong>{{ number_format($card['value'], 0, ',', '.') }}</strong>
                <div>{{ $card['label'] }}</div>
                <small>{{ $card['description'] }}</small>
            </div>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                <th>Serviço</th>
                <th style="width: 160px;">Créditos utilizados</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($creditBreakdown as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td>{{ number_format($item['count'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Nenhum crédito utilizado no período informado.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td>{{ number_format($totalCredits, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
