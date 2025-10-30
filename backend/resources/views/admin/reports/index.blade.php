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
            flex-wrap: wrap;
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
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .admin-chart-card h3 {
            margin: 0;
            font-size: 18px;
            color: var(--text-strong);
        }

        .admin-chart-card p {
            margin: 0;
            font-size: 14px;
            color: var(--text-muted);
        }

        .admin-chart-card canvas {
            width: 100% !important;
            height: 280px !important;
        }

        .admin-highlights {
            margin: 0;
            padding-left: 20px;
            color: var(--text-default);
            font-size: 14px;
            line-height: 1.6;
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
        <div class="admin-chart-grid">
            <div class="admin-chart-card">
                <div>
                    <h3>Consultas por Dia (últimos 30 dias)</h3>
                    <p>Evolução das consultas realizadas diariamente.</p>
                </div>
                <canvas id="chart-daily-consults"></canvas>
            </div>

            <div class="admin-chart-card">
                <div>
                    <h3>Top 5 usuários mais ativos</h3>
                    <p>Ranking de usuários com maior volume de consultas.</p>
                </div>
                <canvas id="chart-top-users"></canvas>
            </div>

            <div class="admin-chart-card">
                <div>
                    <h3>Receita por semana</h3>
                    <p>Distribuição de receita nas últimas quatro semanas.</p>
                </div>
                <canvas id="chart-weekly-revenue"></canvas>
            </div>

            <div class="admin-chart-card">
                <div>
                    <h3>Distribuição de créditos</h3>
                    <p>Percentual de alocação de créditos por categoria.</p>
                </div>
                <canvas id="chart-credit-distribution"></canvas>
            </div>

            <div class="admin-chart-card" style="grid-column: span 2;">
                <div>
                    <h3>Destaques da semana</h3>
                    <p>Principais observações automáticas com base nos dados mockados.</p>
                </div>
                <ul class="admin-highlights">
                    <li>Dia 15 concentrou o pico de consultas, com aumento de 68% sobre a média.</li>
                    <li>Usuários Ana e João responderam por 45% das consultas ativas.</li>
                    <li>A receita da semana 4 superou a média das demais em 12%.</li>
                </ul>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        const reportsState = {
            chartData: @json($chartData),
        };

        (function () {
            const tabButtons = document.querySelectorAll('.admin-tab[data-tab-target]');
            const sections = document.querySelectorAll('[data-tab-section]');
            const chartRefs = {};

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

                    if (target === 'charts') {
                        initCharts();
                    }
                });
            });

            function initCharts() {
                if (chartRefs.initialized || typeof Chart === 'undefined') {
                    return;
                }

                const { dailyConsults, topUsers, weeklyRevenue, creditDistribution } = reportsState.chartData;

                chartRefs.daily = new Chart(document.getElementById('chart-daily-consults'), {
                    type: 'line',
                    data: {
                        labels: dailyConsults.labels,
                        datasets: [
                            {
                                data: dailyConsults.values,
                                borderColor: '#0b4ea2',
                                backgroundColor: 'rgba(11, 78, 162, 0.15)',
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
                                    label: (context) => `${context.formattedValue} consultas`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(148, 163, 184, 0.2)' },
                                ticks: { color: '#64748b' },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.18)' },
                                ticks: { color: '#64748b' },
                            },
                        },
                    },
                });

                chartRefs.topUsers = new Chart(document.getElementById('chart-top-users'), {
                    type: 'bar',
                    data: {
                        labels: topUsers.map((entry) => entry.label),
                        datasets: [
                            {
                                data: topUsers.map((entry) => entry.value),
                                backgroundColor: '#3b82f6',
                                borderRadius: 10,
                                maxBarThickness: 34,
                            },
                        ],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `${context.formattedValue} consultas`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.18)' },
                                ticks: { color: '#64748b' },
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: '#0f172a', font: { weight: '600' } },
                            },
                        },
                    },
                });

                chartRefs.weeklyRevenue = new Chart(document.getElementById('chart-weekly-revenue'), {
                    type: 'bar',
                    data: {
                        labels: weeklyRevenue.labels,
                        datasets: [
                            {
                                data: weeklyRevenue.values,
                                backgroundColor: '#60a5fa',
                                borderRadius: 12,
                                maxBarThickness: 44,
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
                                    label: (context) => `R$ ${context.formattedValue.replace('.', ',')}`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#64748b' },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.18)', drawBorder: false },
                                ticks: {
                                    color: '#64748b',
                                    callback: (value) => `R$${Number(value).toLocaleString('pt-BR')}`,
                                },
                            },
                        },
                    },
                });

                chartRefs.creditDistribution = new Chart(document.getElementById('chart-credit-distribution'), {
                    type: 'doughnut',
                    data: {
                        labels: creditDistribution.map((entry) => entry.label),
                        datasets: [
                            {
                                data: creditDistribution.map((entry) => entry.value),
                                backgroundColor: ['#2563eb', '#f97316', '#0ea5e9', '#10b981'],
                                borderWidth: 0,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    color: '#0f172a',
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `${context.label}: ${context.formattedValue}%`,
                                },
                            },
                        },
                    },
                });

                chartRefs.initialized = true;
            }

            // Initialize charts immediately in case aba de gráficos já esteja visível em carregamentos futuros.
            initCharts();
        })();
    </script>
@endsection
