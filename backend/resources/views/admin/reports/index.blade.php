@extends('admin.layouts.app')

@section('content')
    @php
        $tableColumns = $table['columns'];
        $tableRows = $table['rows'];
        $chartData = $chart;
        $summaryData = $summary;
        $filtersData = $filters;
    @endphp

    <style>
        .reports-header {
            margin-bottom: 28px;
        }

        .reports-header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .reports-header p {
            margin: 6px 0 0;
            font-size: 15px;
            color: var(--text-muted);
        }

        .reports-cards {
            display: grid;
            gap: 18px;
            margin-bottom: 28px;
        }

        @media (min-width: 960px) {
            .reports-cards {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .reports-card-button {
            border: none;
            padding: 0;
            background: none;
            text-align: left;
        }

        .reports-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 24px 28px;
            border-radius: 18px;
            background: var(--surface);
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .reports-card:hover {
            transform: translateY(-2px);
            box-shadow:
                0 28px 60px rgba(15, 23, 42, 0.12),
                0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .reports-card.is-active {
            border-color: rgba(11, 78, 162, 0.4);
            box-shadow:
                0 32px 64px rgba(11, 78, 162, 0.16),
                0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .reports-card__title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .reports-card__value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .reports-card__hint {
            font-size: 13px;
            color: var(--text-muted);
        }

        .reports-filter {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 22px 26px;
            margin-bottom: 28px;
            display: grid;
            gap: 16px;
        }

        @media (min-width: 960px) {
            .reports-filter {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                align-items: flex-end;
            }
        }

        .reports-filter__field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .reports-filter__field label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .reports-filter__field select,
        .reports-filter__field input {
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 12px 14px;
            font-size: 14px;
            color: var(--text-default);
        }

        .reports-filter__actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .reports-filter__submit,
        .reports-filter__export {
            border-radius: 12px;
            border: none;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .reports-filter__submit {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }

        .reports-filter__export {
            background: #eef2f9;
            color: var(--brand-primary);
        }

        .reports-filter__submit:hover,
        .reports-filter__export:hover {
            transform: translateY(-1px);
        }

        .reports-summary {
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .reports-chart-card {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 24px 28px;
            margin-bottom: 26px;
        }

        .reports-chart-card h2 {
            margin: 0 0 6px;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .reports-chart-card p {
            margin: 0 0 16px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .reports-table-wrapper {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            overflow: hidden;
        }

        table.reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.reports-table thead {
            background: #f7f9fc;
        }

        table.reports-table th {
            text-align: left;
            padding: 18px 24px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        table.reports-table td {
            padding: 16px 24px;
            font-size: 14px;
            color: var(--text-default);
            border-top: 1px solid #ecf1f8;
        }

        .reports-empty {
            padding: 32px;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
        }
    </style>

    <header class="reports-header">
        <h1>Relatórios</h1>
        <p>Acompanhe os principais indicadores e extraia insights com os filtros abaixo.</p>
    </header>

    <form id="report-filter-form" class="reports-filter" method="GET">
        <div class="reports-filter__field">
            <label for="report-type">Tipo de relatório</label>
            <select id="report-type" name="report_type">
                @foreach ($filtersData['report_options'] as $value => $label)
                    <option value="{{ $value }}" @selected($filtersData['report_type'] === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="reports-filter__field">
            <label for="report-period">Agrupamento</label>
            <select id="report-period" name="period" data-period-select>
                @foreach ($filtersData['period_options'] as $value => $label)
                    <option value="{{ $value }}" @selected($filtersData['period'] === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="reports-filter__field">
            <label for="report-reference">Referência</label>
            <input
                id="report-reference"
                name="reference"
                data-period-reference
                value="{{ $filtersData['reference_input'] }}"
            >
        </div>

        <div class="reports-filter__field">
            <label for="report-search">Pesquisar</label>
            <input
                type="search"
                id="report-search"
                name="search"
                value="{{ $filtersData['search'] }}"
                placeholder="{{ $searchPlaceholder }}"
            >
        </div>

        <div class="reports-filter__field reports-filter__actions">
            <button type="submit" class="reports-filter__submit">
                Aplicar filtros
            </button>

            <button
                type="submit"
                class="reports-filter__export"
                formaction="{{ route('admin.reports.export') }}"
            >
                Exportar CSV
            </button>
        </div>
    </form>

    <section class="reports-cards">
        @foreach ($statCards as $card)
            <button
                type="submit"
                class="reports-card-button"
                form="report-filter-form"
                name="report_type"
                value="{{ $card['key'] }}"
            >
                <article class="reports-card {{ $card['active'] ? 'is-active' : '' }}">
                    <span class="reports-card__title">{{ $card['title'] }}</span>
                    <span class="reports-card__value">{{ $card['value'] }}</span>
                    <span class="reports-card__hint">
                        Clique para focar no indicador
                    </span>
                </article>
            </button>
        @endforeach
    </section>

    <p class="reports-summary">
        Exibindo <strong>{{ number_format($summaryData['total'], 0, ',', '.') }}</strong>
        {{ $filtersData['report_options'][$filtersData['report_type']] }}
        no período de <strong>{{ $filtersData['period_label'] }}</strong>.
    </p>

    <section class="reports-chart-card">
        <h2>Evolução por {{ strtolower($filtersData['period_options'][$filtersData['period']]) }}</h2>
        <p>Mantenha o acompanhamento do volume registrado em cada período.</p>

        @if (count($chartData['labels']) > 0)
            <canvas id="reports-chart" width="600" height="320"></canvas>
        @else
            <div class="reports-empty">
                Não há dados suficientes para exibir o gráfico com os filtros informados.
            </div>
        @endif
    </section>

    <div class="reports-table-wrapper">
        <table class="reports-table">
            <thead>
                <tr>
                    @foreach ($tableColumns as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($tableRows as $row)
                    <tr>
                        @foreach ($tableColumns as $column)
                            <td>{{ $row[$column['key']] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($tableColumns) }}">
                            <div class="reports-empty">
                                Nenhum registro encontrado para os filtros informados.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const periodSelect = document.querySelector('[data-period-select]');
            const referenceInput = document.querySelector('[data-period-reference]');

            const inputTypes = {
                day: { type: 'date', placeholder: 'YYYY-MM-DD' },
                week: { type: 'week', placeholder: 'YYYY-Www' },
                month: { type: 'month', placeholder: 'YYYY-MM' },
                year: { type: 'number', placeholder: 'YYYY' },
            };

            function syncReferenceInput() {
                const selected = periodSelect.value;
                const meta = inputTypes[selected] ?? inputTypes.month;
                referenceInput.type = meta.type;
                referenceInput.placeholder = meta.placeholder;

                if (meta.type === 'number') {
                    referenceInput.min = '2000';
                    referenceInput.max = new Date().getFullYear().toString();
                    referenceInput.step = '1';
                } else {
                    referenceInput.removeAttribute('min');
                    referenceInput.removeAttribute('max');
                    referenceInput.removeAttribute('step');
                }
            }

            if (periodSelect && referenceInput) {
                syncReferenceInput();
                periodSelect.addEventListener('change', syncReferenceInput);
            }

            const chartElement = document.getElementById('reports-chart');
            if (!chartElement || typeof Chart === 'undefined') {
                return;
            }

            const chartData = @json($chartData);

            if (!Array.isArray(chartData.labels) || chartData.labels.length === 0) {
                return;
            }

            new Chart(chartElement, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: chartData.dataset_label,
                            data: chartData.values,
                            borderColor: '#0b4ea2',
                            backgroundColor: 'rgba(11, 78, 162, 0.16)',
                            pointBackgroundColor: '#0b4ea2',
                            pointRadius: 4,
                            tension: 0.35,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.formattedValue}${chartData.value_suffix}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { autoSkip: true, maxTicksLimit: 12 },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        })();
    </script>
@endsection
