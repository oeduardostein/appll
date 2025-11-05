@extends('admin.layouts.app')

@section('content')
    @php
        $tableColumns = $tableColumns ?? [];
        $tableRows = $tableRows ?? [];
        $filtersData = $filters ?? [];
        $searchValue = $filtersData['search'] ?? '';
        $periodLabel = $filtersData['period_label'] ?? '';
    @endphp

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

        .admin-stat-card-button {
            all: unset;
            display: block;
        }

        .admin-stat-card.is-active {
            border: 1px solid rgba(11, 78, 162, 0.35);
            box-shadow:
                0 28px 60px rgba(11, 78, 162, 0.16),
                0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .admin-filter-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.36);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 80;
        }

        .admin-filter-modal.is-visible {
            display: flex;
        }

        .admin-filter-modal__content {
            background: #fff;
            border-radius: 18px;
            padding: 24px 28px;
            width: 100%;
            max-width: 420px;
            box-shadow:
                0 32px 64px rgba(15, 23, 42, 0.22),
                0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .admin-filter-modal__content h2 {
            margin: 0 0 18px;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .admin-filter-modal__fields {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 20px;
        }

        .admin-filter-modal__field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
        }

        .admin-filter-modal__field label {
            font-weight: 600;
            color: var(--text-muted);
        }

        .admin-filter-modal__field select,
        .admin-filter-modal__field input {
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 12px 14px;
            font-size: 14px;
            color: var(--text-default);
        }

        .admin-filter-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .admin-filter-modal__actions button {
            border-radius: 12px;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .admin-filter-modal__actions button[type="button"] {
            background: #f3f4f6;
            color: var(--text-default);
        }

        .admin-filter-modal__actions button[type="submit"] {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }
    </style>

    <header class="admin-reports__header">
        <h1>Relatórios</h1>
        <p>Centralize os principais indicadores da plataforma e acompanhe a performance.</p>
    </header>

    <section class="stat-grid" style="margin-bottom: 32px;">
        @foreach ($statCards as $card)
            <button type="button" class="admin-stat-card-button" data-report-card data-report="{{ $card['key'] }}">
                <x-admin.stat-card
                    :title="$card['title']"
                    :value="$card['value']"
                    :class="$card['active'] ? 'is-active' : ''"
                />
            </button>
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

        </div>

        <form class="admin-search" data-reports-search method="GET" action="{{ route('admin.reports.index') }}">
            <svg width="17" height="17" viewBox="0 0 20 20" fill="none">
                <path d="M18 18l-4.35-4.35m1.35-4.65a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" stroke="#8193ae"
                    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <input type="search" placeholder="{{ $searchPlaceholder }}" name="search" value="{{ $searchValue }}" />
            <input type="hidden" name="report_type" value="{{ $filtersData['report_type'] ?? 'new_users' }}">
            <input type="hidden" name="period" value="{{ $filtersData['period'] ?? 'month' }}">
            <input type="hidden" name="reference" value="{{ $filtersData['reference'] ?? '' }}">
            <button type="submit" aria-label="Pesquisar" style="display: none;"></button>
        </form>
    </div>

    <p style="margin: -8px 0 24px; color: var(--text-muted); font-size: 14px;">
        Exibindo <strong>{{ number_format($summaryTotal, 0, ',', '.') }}</strong>
        {{ $filtersData['report_options'][$filtersData['report_type']] ?? '' }}
        no período de <strong>{{ $periodLabel }}</strong>.
    </p>

    <div class="admin-filter-modal" data-filter-modal hidden>
        <div class="admin-filter-modal__content">
            <form data-filter-form>
                <h2>Definir filtros</h2>

                <div class="admin-filter-modal__fields">
                    <div class="admin-filter-modal__field">
                        <label for="filter-period">Agrupar por</label>
                        <select id="filter-period" name="period">
                            @foreach ($filtersData['period_options'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filtersData['period'] ?? 'month') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="admin-filter-modal__field">
                        <label for="filter-reference">Referência</label>
                        <input
                            id="filter-reference"
                            name="reference"
                            value="{{ $filtersData['reference'] ?? '' }}"
                        >
                        <small style="color: var(--text-muted); font-size: 12px;">
                            Ajuste conforme o período escolhido (ex: 2025-11 para mês, 2025-W48 para semana).
                        </small>
                    </div>
                </div>

                <div class="admin-filter-modal__actions">
                    <button type="button" data-action="close-filter">Cancelar</button>
                    <button type="submit">Aplicar</button>
                </div>
            </form>
        </div>
    </div>

    <section class="admin-reports__section is-visible" role="region">
        <div class="table-wrapper">
            <table class="admin-table">
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
                                @php
                                    $value = $row[$column['key']] ?? '—';
                                @endphp

                                @if ($column['key'] === 'client')
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <strong style="color: var(--text-strong);">{{ $value }}</strong>
                                        </div>
                                    </td>
                                @else
                                    <td>{{ $value }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($tableColumns) }}" style="text-align: center; padding: 28px;">
                                Nenhum registro encontrado para os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const chartRefs = {};
            const searchForm = document.querySelector('[data-reports-search]');
            const reportCards = document.querySelectorAll('[data-report-card]');
            const filterModal = document.querySelector('[data-filter-modal]');
            const filterForm = filterModal?.querySelector('[data-filter-form]');
            const periodSelect = filterForm?.querySelector('select[name="period"]');
            const referenceInput = filterForm?.querySelector('input[name="reference"]');
            const exportButton = document.querySelector('[data-action="export-reports"]');
            const exportBaseUrl = @json($exportBaseUrl);

            const syncReferenceAttributes = () => {
                if (!periodSelect || !referenceInput) return;

                const selected = periodSelect.value;
                const map = {
                    day: { type: 'date', placeholder: 'YYYY-MM-DD' },
                    week: { type: 'week', placeholder: 'YYYY-Www' },
                    month: { type: 'month', placeholder: 'YYYY-MM' },
                    year: { type: 'number', placeholder: 'YYYY' },
                };

                const meta = map[selected] ?? map.month;
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
            };

            const setHiddenField = (name, value) => {
                if (!searchForm) return;
                const field = searchForm.querySelector(`[name="${name}"]`);
                if (field) {
                    field.value = value;
                }
            };

            reportCards.forEach((button) => {
                button.addEventListener('click', () => {
                    const report = button.getAttribute('data-report');
                    if (!report || !searchForm) return;

                    setHiddenField('report_type', report);
                    searchForm.submit();
                });
            });

            document.querySelector('[data-action="open-filter-modal"]')?.addEventListener('click', () => {
                if (!filterModal || !filterForm || !searchForm) return;

                const periodValue = searchForm.querySelector('[name="period"]')?.value ?? 'month';
                const referenceValue = searchForm.querySelector('[name="reference"]')?.value ?? '';

                periodSelect.value = periodValue;
                referenceInput.value = referenceValue;

                syncReferenceAttributes();
                filterModal.classList.add('is-visible');
            });

            periodSelect?.addEventListener('change', syncReferenceAttributes);

            filterForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!searchForm) return;

                setHiddenField('period', periodSelect?.value ?? 'month');
                setHiddenField('reference', referenceInput?.value ?? '');

                filterModal?.classList.remove('is-visible');
                searchForm.submit();
            });

            filterModal?.querySelector('[data-action="close-filter"]')?.addEventListener('click', () => {
                filterModal.classList.remove('is-visible');
            });

            filterModal?.addEventListener('click', (event) => {
                if (event.target === filterModal) {
                    filterModal.classList.remove('is-visible');
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    filterModal?.classList.remove('is-visible');
                }
            });

            exportButton?.addEventListener('click', () => {
                if (!searchForm) return;
                const formData = new FormData(searchForm);
                const params = new URLSearchParams(formData);
                window.location.href = `${exportBaseUrl}?${params.toString()}`;
            });
        })();
    </script>
@endsection
