@extends('admin.layouts.app')

@section('content')
    <style>
        .admin-reports__header {
            margin-bottom: 32px;
        }

        .admin-reports__header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .admin-reports__header p {
            margin: 8px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }

        .admin-reports__section {
            display: none;
        }

        .admin-reports__section.is-visible {
            display: block;
        }

        .admin-reports__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .admin-reports__actions-left {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .admin-action-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid #d7deeb;
            background: #f7f9fc;
            color: var(--text-default);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .admin-action-button svg {
            flex-shrink: 0;
        }

        .admin-action-button--primary {
            background: var(--brand-primary);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }

        .admin-action-button--ghost {
            background: #fff;
            border-color: #d7deeb;
        }

        .admin-tabs {
            display: inline-flex;
            background: #eef2f9;
            border-radius: 999px;
            padding: 4px;
            gap: 4px;
        }

        .admin-tab {
            border: none;
            border-radius: 999px;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            background: transparent;
            color: var(--text-default);
            cursor: pointer;
            transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .admin-tab.is-active {
            background: #fff;
            color: var(--brand-primary);
            box-shadow: 0 8px 18px rgba(11, 78, 162, 0.18);
        }

        .admin-tab:not(.is-active):hover {
            transform: translateY(-1px);
        }

        .admin-search {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid #d7deeb;
            background: #fff;
            min-width: 260px;
        }

        .admin-search input {
            border: none;
            outline: none;
            font-size: 14px;
            flex: 1 1 auto;
            background: transparent;
            color: var(--text-default);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table thead {
            background: #f7f9fc;
        }

        .admin-table th {
            text-align: left;
            padding: 18px 24px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .admin-table td {
            padding: 16px 24px;
            font-size: 14px;
            border-top: 1px solid #ecf1f8;
        }

        .admin-chart-grid {
            display: grid;
            gap: 20px;
        }

        @media (min-width: 1024px) {
            .admin-chart-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .admin-chart-card {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 24px 28px;
        }

        .admin-chart-card h3 {
            margin: 0 0 12px;
            font-size: 18px;
            color: var(--text-strong);
        }

        .admin-chart-card p {
            margin: 0 0 20px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .admin-bar-chart {
            display: grid;
            gap: 10px;
        }

        .admin-bar-chart__item {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--text-default);
        }

        .admin-bar-chart__bar {
            position: relative;
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: #ecf1f8;
        }

        .admin-bar-chart__fill {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(135deg, #0b4ea2, #2f6bc5);
            transform-origin: left;
            transform: scaleX(0.1);
        }

        .admin-donut-chart {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .admin-donut-chart__meter {
            width: 140px;
            height: 140px;
            position: relative;
        }

        .admin-donut-chart__meter svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .admin-donut-chart__legend {
            display: grid;
            gap: 10px;
            font-size: 14px;
            color: var(--text-default);
        }

        .admin-donut-chart__legend-item {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .admin-legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
        }

        .admin-inline-grid {
            display: grid;
            gap: 20px;
        }

        @media (min-width: 1024px) {
            .admin-inline-grid {
                grid-template-columns: 1.2fr 0.8fr;
            }
        }
    </style>

    <header class="admin-reports__header">
        <h1>Relatórios</h1>
        <p>Centralize os principais indicadores da plataforma e acompanhe a performance.</p>
    </header>

    <section class="stat-grid" style="margin-bottom: 32px;">
        @foreach ($statCards as $card)
            <x-admin.stat-card
                :title="$card['title']"
                :value="$card['value']"
            />
        @endforeach
    </section>

    <div class="admin-reports__actions">
        <div class="admin-reports__actions-left">
            <button type="button" class="admin-action-button admin-action-button--ghost" data-action="open-filter-modal">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M2.667 4h10.666M4 4c0 3.2 2.133 5.333 4 5.333S12 7.2 12 4M6 12h4"
                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Filtros
            </button>

            <button type="button" class="admin-action-button admin-action-button--primary" data-action="export-reports">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M12.667 10v2.667H3.333V10M8 9.333l-2.667-2.666M8 9.333l2.667-2.666M8 9.333V2"
                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Exportar
            </button>

            <div class="admin-tabs" role="tablist">
                <button type="button" class="admin-tab is-active" data-tab-target="table" role="tab" aria-selected="true">
                    Dados em tabela
                </button>
                <button type="button" class="admin-tab" data-tab-target="charts" role="tab" aria-selected="false">
                    Visão gráfica
                </button>
            </div>
        </div>

        <form class="admin-search" data-reports-search>
            <svg width="17" height="17" viewBox="0 0 20 20" fill="none">
                <path d="M18 18l-4.35-4.35m1.35-4.65a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" stroke="#8193ae"
                    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <input type="search" placeholder="Pesquisar" name="search" />
            <button type="submit" aria-label="Pesquisar" style="display: none;"></button>
        </form>
    </div>

    <section class="admin-reports__section is-visible" data-tab-section="table" role="tabpanel">
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Plano</th>
                        <th>Créditos utilizados</th>
                        <th>Taxa de conversão</th>
                        <th>Última atividade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tableRows as $row)
                        <tr>
                            <td>{{ $row['id'] }}</td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <strong style="color: var(--text-strong);">{{ $row['client'] }}</strong>
                                    <span style="color: var(--text-muted); font-size: 13px;">{{ $row['email'] }}</span>
                                </div>
                            </td>
                            <td>{{ $row['plan'] }}</td>
                            <td>{{ $row['credits_used'] }} créditos</td>
                            <td>{{ number_format($row['conversion_rate'] * 100, 1, ',', '.') }}%</td>
                            <td>{{ $row['last_activity'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-reports__section" data-tab-section="charts" role="tabpanel" aria-hidden="true">
        <div class="admin-inline-grid">
            <div class="admin-chart-card">
                <h3>Novos cadastros na semana</h3>
                <p>Evolução diária de inscrições na plataforma.</p>
                <div class="admin-bar-chart" data-bar-chart></div>
            </div>

            <div class="admin-chart-card">
                <h3>Distribuição de planos</h3>
                <p>Participação percentual dos planos ativos.</p>
                <div class="admin-donut-chart" data-donut-chart="plans"></div>
            </div>
        </div>

        <div class="admin-chart-grid" style="margin-top: 20px;">
            <div class="admin-chart-card">
                <h3>Uso de créditos por status de campanha</h3>
                <p>Comparativo geral de utilização dos créditos.</p>
                <div class="admin-donut-chart" data-donut-chart="credits"></div>
            </div>

            <div class="admin-chart-card">
                <h3>Destaques da semana</h3>
                <p>Principais observações automáticas com base nos dados mockados.</p>
                <ul style="margin: 0; padding-left: 20px; color: var(--text-default); font-size: 14px; line-height: 1.6;">
                    <li>Plano Premium registrou crescimento de 12% nas conversões.</li>
                    <li>Campanhas ativas consomem 62% dos créditos disponíveis.</li>
                    <li>Quinta-feira concentrou a maior quantidade de cadastros.</li>
                </ul>
            </div>
        </div>
    </section>

    <script>
        const reportsState = {
            chartData: @json($chartData),
        };

        (function () {
            const tabButtons = document.querySelectorAll('.admin-tab[data-tab-target]');
            const sections = document.querySelectorAll('[data-tab-section]');

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-tab-target');

                    tabButtons.forEach((tab) => {
                        const isActive = tab === button;
                        tab.classList.toggle('is-active', isActive);
                        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    sections.forEach((section) => {
                        const matches = section.getAttribute('data-tab-section') === target;
                        section.classList.toggle('is-visible', matches);
                        section.setAttribute('aria-hidden', matches ? 'false' : 'true');
                    });
                });
            });

            function renderBarChart(container, labels, values) {
                if (!container) {
                    return;
                }

                const maxValue = Math.max(...values, 1);
                container.innerHTML = '';

                labels.forEach((label, index) => {
                    const item = document.createElement('div');
                    item.className = 'admin-bar-chart__item';

                    const labelElement = document.createElement('span');
                    labelElement.textContent = label;

                    const barWrapper = document.createElement('div');
                    barWrapper.className = 'admin-bar-chart__bar';

                    const fill = document.createElement('span');
                    fill.className = 'admin-bar-chart__fill';
                    const ratio = values[index] / maxValue;
                    fill.style.transform = `scaleX(${ratio})`;

                    barWrapper.appendChild(fill);

                    const valueElement = document.createElement('strong');
                    valueElement.style.fontSize = '14px';
                    valueElement.style.color = 'var(--text-strong)';
                    valueElement.textContent = `${values[index]} cad.`;

                    item.appendChild(labelElement);
                    item.appendChild(barWrapper);
                    item.appendChild(valueElement);

                    container.appendChild(item);
                });
            }

            function renderDonutChart(container, dataset, palette) {
                if (!container) {
                    return;
                }

                const total = dataset.reduce((sum, entry) => sum + entry.value, 0) || 1;
                const svgNamespace = 'http://www.w3.org/2000/svg';

                const meter = document.createElement('div');
                meter.className = 'admin-donut-chart__meter';

                const svg = document.createElementNS(svgNamespace, 'svg');
                const radius = 60;
                const strokeWidth = 18;
                const circumference = 2 * Math.PI * radius;
                let offset = circumference;

                const backdrop = document.createElementNS(svgNamespace, 'circle');
                backdrop.setAttribute('cx', '70');
                backdrop.setAttribute('cy', '70');
                backdrop.setAttribute('r', String(radius));
                backdrop.setAttribute('fill', 'transparent');
                backdrop.setAttribute('stroke', '#ecf1f8');
                backdrop.setAttribute('stroke-width', String(strokeWidth));
                svg.appendChild(backdrop);

                dataset.forEach((entry, index) => {
                    const circle = document.createElementNS(svgNamespace, 'circle');
                    circle.setAttribute('cx', '70');
                    circle.setAttribute('cy', '70');
                    circle.setAttribute('r', String(radius));
                    circle.setAttribute('fill', 'transparent');
                    circle.setAttribute('stroke', palette[index % palette.length]);
                    circle.setAttribute('stroke-width', String(strokeWidth));
                    const ratio = entry.value / total;
                    circle.setAttribute('stroke-dasharray', `${circumference * ratio} ${circumference}`);
                    circle.setAttribute('stroke-dashoffset', String(offset));
                    circle.setAttribute('stroke-linecap', 'round');
                    offset -= circumference * ratio;
                    svg.appendChild(circle);
                });

                meter.appendChild(svg);

                const legend = document.createElement('div');
                legend.className = 'admin-donut-chart__legend';

                dataset.forEach((entry, index) => {
                    const item = document.createElement('div');
                    item.className = 'admin-donut-chart__legend-item';

                    const dot = document.createElement('span');
                    dot.className = 'admin-legend-dot';
                    dot.style.background = palette[index % palette.length];

                    const label = document.createElement('span');
                    label.innerHTML = `<strong style="color: var(--text-strong);">${entry.label}</strong> — ${entry.value}%`;

                    item.appendChild(dot);
                    item.appendChild(label);
                    legend.appendChild(item);
                });

                container.innerHTML = '';
                container.appendChild(meter);
                container.appendChild(legend);
            }

            renderBarChart(
                document.querySelector('[data-bar-chart]'),
                reportsState.chartData.weeklyLabels,
                reportsState.chartData.weeklySignups
            );

            const palette = ['#0b4ea2', '#2f6bc5', '#587ed0'];
            renderDonutChart(
                document.querySelector('[data-donut-chart="plans"]'),
                reportsState.chartData.planDistribution,
                palette
            );

            renderDonutChart(
                document.querySelector('[data-donut-chart="credits"]'),
                reportsState.chartData.creditUsage,
                ['#0b4ea2', '#93c5fd', '#c7d2fe']
            );
        })();
    </script>
@endsection
