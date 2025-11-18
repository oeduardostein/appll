@extends('admin.layouts.app')

@section('content')
    <style>
        .detail-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--brand-primary);
            text-decoration: none;
            margin-bottom: 20px;
        }

        .detail-back-link svg {
            width: 16px;
            height: 16px;
        }

        .detail-hero {
            padding: 32px 36px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 1fr);
            gap: 28px;
            align-items: flex-start;
        }

        @media (max-width: 860px) {
            .detail-hero {
                grid-template-columns: 1fr;
            }
        }

        .detail-hero__title {
            font-size: 30px;
            margin: 0 0 6px;
            color: var(--text-strong);
        }

        .detail-hero__subtitle {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .detail-hero__stats {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .detail-hero__stat {
            min-width: 140px;
            padding: 12px 16px;
            border-radius: 14px;
            background: var(--surface-muted);
            font-size: 13px;
            color: var(--text-muted);
        }

        .detail-hero__stat strong {
            display: block;
            margin-top: 6px;
            font-size: 18px;
            color: var(--text-strong);
        }

        .detail-hero__status .status-pill {
            margin-top: 8px;
        }

        .detail-filter-card {
            padding: 22px 28px;
            margin-bottom: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
            justify-content: space-between;
        }

        .detail-filter-card h2 {
            margin: 0;
            font-size: 18px;
            color: var(--text-strong);
        }

        .detail-filter-card p {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .detail-filter-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .detail-filter-form select {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #d7deeb;
            font-size: 14px;
            background: #f9fbff;
            cursor: pointer;
            min-width: 200px;
        }

        .detail-summary {
            margin-bottom: 32px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .detail-summary__card {
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .detail-summary__card h3 {
            margin: 4px 0;
            font-size: 26px;
            color: var(--text-strong);
        }

        .detail-summary__card span {
            font-size: 13px;
            color: var(--text-muted);
        }

        .detail-summary__amount {
            font-weight: 600;
            color: var(--brand-primary);
        }

        .credit-breakdown {
            padding: 28px 32px;
        }

        .credit-breakdown__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .credit-breakdown__header h3 {
            margin: 0;
            font-size: 20px;
            color: var(--text-strong);
        }

        .credit-breakdown__header p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .credit-breakdown__total {
            text-align: right;
        }

        .credit-breakdown__total span {
            font-size: 13px;
            color: var(--text-muted);
            display: block;
        }

        .credit-breakdown__total strong {
            display: block;
            font-size: 30px;
            color: var(--text-strong);
        }

        .credit-breakdown__list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .credit-breakdown__item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 0;
            border-bottom: 1px solid #ecf1f8;
        }

        .credit-breakdown__item:last-child {
            border-bottom: none;
        }

        .credit-breakdown__label {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .credit-breakdown__count {
            text-align: right;
        }

        .credit-breakdown__count strong {
            display: block;
            font-size: 20px;
            color: var(--text-strong);
        }

        .credit-breakdown__count span {
            font-size: 13px;
            color: var(--text-muted);
        }

        .credit-breakdown__amount {
            font-size: 14px;
            color: var(--brand-primary);
            font-weight: 600;
        }

        .credit-breakdown__empty {
            padding: 24px 0;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
        }

        .admin-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background-color 160ms ease, transform 160ms ease;
        }

        .admin-button svg {
            width: 16px;
            height: 16px;
        }

        .admin-button--ghost {
            background: #eef2f9;
            color: var(--text-default);
        }

        .admin-button--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.25);
        }

        .admin-button:hover {
            transform: translateY(-1px);
        }

        .admin-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 80;
        }

        .admin-modal.is-visible {
            display: flex;
        }

        .admin-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
        }

        .admin-modal__panel {
            position: relative;
            background: #fff;
            border-radius: 20px;
            padding: 32px;
            width: min(420px, calc(100% - 32px));
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.25);
        }

        .admin-modal__close {
            position: absolute;
            top: 14px;
            right: 14px;
            background: transparent;
            border: none;
            font-size: 22px;
            cursor: pointer;
        }

        .admin-modal__header h3 {
            margin: 0 0 8px;
            font-size: 20px;
            color: var(--text-strong);
        }

        .export-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 18px 0 24px;
        }

        .export-option {
            border: 1px solid #d7deeb;
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .export-option input {
            width: 18px;
            height: 18px;
        }

        .export-option__label {
            font-weight: 600;
            color: var(--text-strong);
        }

        .export-option__caption {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
        }

        .admin-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
    </style>

    @php
        $formatCurrency = static fn (float $value): string => 'R$ ' . number_format($value, 2, ',', '.');
    @endphp

    <a class="detail-back-link" href="{{ route('admin.clients.index') }}">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
        Voltar para a lista
    </a>

    <section class="admin-card detail-hero">
        <div class="detail-hero__identity">
            <p class="detail-hero__subtitle">Cliente</p>
            <h1 class="detail-hero__title">{{ $user->name }}</h1>
            <p class="detail-hero__subtitle">{{ $user->email }}</p>
        </div>
        <div class="detail-hero__stats">
            <div class="detail-hero__stat">
                ID
                <strong>#{{ $user->id }}</strong>
            </div>
            <div class="detail-hero__stat detail-hero__status">
                Status
                <span class="status-pill {{ $user->is_active ? '' : 'inactive' }}">
                    {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                </span>
            </div>
            <div class="detail-hero__stat">
                Créditos atuais
                <strong>{{ number_format((int) ($user->credits ?? 0), 0, ',', '.') }}</strong>
            </div>
        </div>
    </section>

    <section class="admin-card detail-filter-card">
        <div>
            <h2>Créditos utilizados</h2>
            <p>Visualizando dados referentes a {{ \Illuminate\Support\Str::lower($selectedMonthLabel) }}.</p>
        </div>
        <div class="detail-filter-actions">
            <form method="GET" class="detail-filter-form">
                <select id="month-filter" name="month" onchange="this.form.submit()">
                    @foreach ($availableMonths as $option)
                        <option value="{{ $option['value'] }}" @selected($option['value'] === $selectedMonth)>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="admin-button admin-button--ghost" data-action="open-export-modal">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v10m0 0 4-4m-4 4-4-4" stroke="currentColor" stroke-width="1.6"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M5 19h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
                Exportar
            </button>
        </div>
    </section>

    <section class="stat-grid detail-summary">
        @foreach ($creditSummary as $card)
            <article class="admin-card detail-summary__card">
                <span>{{ $card['label'] }}</span>
                <h3>{{ number_format($card['value'], 0, ',', '.') }}</h3>
                <span>{{ $card['description'] }}</span>
                @if (array_key_exists('amount', $card))
                    <span class="detail-summary__amount">{{ $formatCurrency((float) $card['amount']) }}</span>
                @endif
            </article>
        @endforeach
    </section>

    <section class="admin-card credit-breakdown">
        <header class="credit-breakdown__header">
            <div>
                <h3>Distribuição por serviço</h3>
                <p>{{ $selectedMonthLabel }}</p>
            </div>
            <div class="credit-breakdown__total">
                <span>Total</span>
                <strong>{{ number_format($totalCredits, 0, ',', '.') }}</strong>
                <span>{{ \Illuminate\Support\Str::plural('crédito', $totalCredits) }} utilizados</span>
                <span class="credit-breakdown__amount">{{ $formatCurrency((float) $totalAmount) }}</span>
            </div>
        </header>

        <ul class="credit-breakdown__list">
            @forelse ($creditBreakdown as $item)
                <li class="credit-breakdown__item">
                    <span class="credit-breakdown__label">{{ $item['label'] }}</span>
                    <div class="credit-breakdown__count">
                        <strong>{{ number_format($item['count'], 0, ',', '.') }}</strong>
                        <span>{{ $item['count'] === 1 ? 'crédito' : 'créditos' }}</span>
                        <span class="credit-breakdown__amount">{{ $formatCurrency((float) ($item['amount'] ?? 0)) }}</span>
                    </div>
                </li>
            @empty
                <li class="credit-breakdown__empty">
                    Nenhum crédito utilizado neste período.
                </li>
            @endforelse
        </ul>
    </section>

    <div class="admin-modal" data-modal="export" aria-hidden="true">
        <div class="admin-modal__backdrop" data-modal-close></div>
        <div class="admin-modal__panel" role="dialog" aria-modal="true">
            <button type="button" class="admin-modal__close" data-modal-close aria-label="Fechar">×</button>
            <header class="admin-modal__header">
                <h3>Exportar relatório</h3>
                <p style="margin: 0; color: var(--text-muted); font-size: 14px;">
                    Escolha o formato desejado para exportar os dados filtrados.
                </p>
            </header>
            <form method="POST" action="{{ route('admin.clients.export', $user) }}" data-export-form>
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}" data-export-month />
                <div class="export-options">
                    <label class="export-option">
                        <input type="radio" name="format" value="pdf" checked />
                        <div>
                            <span class="export-option__label">PDF</span>
                            <span class="export-option__caption">Relatório pronto para impressão.</span>
                        </div>
                    </label>
                    <label class="export-option">
                        <input type="radio" name="format" value="csv" />
                        <div>
                            <span class="export-option__label">CSV</span>
                            <span class="export-option__caption">Dados em tabela para planilhas.</span>
                        </div>
                    </label>
                </div>
                <div class="admin-modal__actions">
                    <button type="button" class="admin-button admin-button--ghost" data-modal-close>Cancelar</button>
                    <button type="submit" class="admin-button admin-button--primary">Gerar relatório</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.querySelector('[data-modal="export"]');
            const openButton = document.querySelector('[data-action="open-export-modal"]');
            const closeTriggers = modal?.querySelectorAll('[data-modal-close]');
            const monthSelect = document.getElementById('month-filter');
            const monthInput = modal?.querySelector('[data-export-month]');
            const modalBackdrop = modal?.querySelector('.admin-modal__backdrop');

            if (!modal || !openButton) {
                return;
            }

            const setMonthValue = () => {
                if (monthInput && monthSelect) {
                    monthInput.value = monthSelect.value;
                }
            };

            const closeModal = () => {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            };

            openButton.addEventListener('click', () => {
                setMonthValue();
                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');
            });

            closeTriggers?.forEach((trigger) => {
                trigger.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modalBackdrop) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-visible')) {
                    closeModal();
                }
            });

            monthSelect?.addEventListener('change', setMonthValue);
        })();
    </script>
@endsection
