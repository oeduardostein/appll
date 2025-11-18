@extends('admin.layouts.app')

@section('content')
    @php
        $locale = 'pt_BR';
        $monthLabelRaw = $selectedMonth->clone()->locale($locale)->translatedFormat('F \\d\\e Y');
        $monthLabel = Illuminate\Support\Str::ucfirst($monthLabelRaw);
        $formatCurrency = static fn (float $value): string => 'R$ ' . number_format($value, 2, ',', '.');
    @endphp

    <style>
        .credit-management__header {
            margin-bottom: 28px;
        }

        .credit-management__header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .credit-management__header p {
            margin: 10px 0 0;
            font-size: 15px;
            color: var(--text-muted);
        }

        .credit-management__filters {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .credit-management__select {
            display: inline-flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .credit-management__select select {
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 12px 16px;
            font-size: 14px;
            color: var(--text-default);
            background: #fff;
            min-width: 220px;
        }

        .credit-management__search {
            display: inline-flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
            color: var(--text-muted);
            flex: 1;
            min-width: 240px;
        }

        .credit-management__search-field {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .credit-management__search-input {
            flex: 1;
            min-width: 200px;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 12px 16px;
            font-size: 14px;
            color: var(--text-default);
            background: #fff;
        }

        .credit-management__search-button {
            border-radius: 12px;
            border: none;
            padding: 11px 18px;
            font-weight: 600;
            font-size: 14px;
            background: var(--brand-primary);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(11, 78, 162, 0.2);
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .credit-management__search-button:hover {
            transform: translateY(-1px);
        }

        .credit-management__search-clear {
            border: none;
            background: transparent;
            color: var(--brand-primary);
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            text-decoration: none;
        }

        .credit-management__alert {
            border-radius: 14px;
            padding: 14px 18px;
            background: #ecfdf5;
            color: #047857;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .credit-management__alert svg {
            flex-shrink: 0;
        }

        .credit-management__status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 6px 14px;
            font-weight: 600;
            font-size: 13px;
        }

        .credit-management__status-pill--paid {
            background: #dcfce7;
            color: #166534;
        }

        .credit-management__status-pill--pending {
            background: #fee2e2;
            color: #b91c1c;
        }

        .credit-management__status-pill--neutral {
            background: #e5e7eb;
            color: #1f2937;
        }

        .credit-management__user-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
        }

        .credit-management__user-status--active {
            background: rgba(34, 197, 94, 0.16);
            color: #15803d;
        }

        .credit-management__user-status--inactive {
            background: rgba(239, 68, 68, 0.16);
            color: #b91c1c;
        }

        .credit-management__amount {
            display: block;
            margin-top: 4px;
            font-weight: 600;
            color: var(--brand-primary);
        }

        .credit-management__actions {
            display: inline-flex;
            gap: 12px;
        }

        .credit-management__button {
            border-radius: 12px;
            border: none;
            padding: 10px 16px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: transform 160ms ease, box-shadow 160ms ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .credit-management__button--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }

        .credit-management__button--danger {
            background: #fee2e2;
            color: #b91c1c;
            box-shadow: none;
        }

        .credit-management__button:hover {
            transform: translateY(-1px);
        }

        .credit-management__empty {
            padding: 40px;
            text-align: center;
            color: var(--text-muted);
            font-size: 15px;
        }

        .credit-management__modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(12, 21, 45, 0.42);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 70;
        }

        .credit-management__modal-backdrop.is-visible {
            display: flex;
        }

        .credit-management__modal {
            background: #fff;
            border-radius: 20px;
            box-shadow:
                0 20px 60px rgba(15, 23, 42, 0.18),
                0 1px 0 rgba(255, 255, 255, 0.7);
            max-width: 420px;
            width: 100%;
            padding: 28px 28px 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .credit-management__modal h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .credit-management__modal p {
            margin: 0;
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .credit-management__modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .credit-management__modal-button {
            border-radius: 12px;
            border: 1px solid transparent;
            padding: 10px 18px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .credit-management__modal-button:hover {
            transform: translateY(-1px);
        }

        .credit-management__modal-button--secondary {
            background: #f7f9fc;
            color: var(--text-default);
            border-color: #d7deeb;
        }

        .credit-management__modal-button--danger {
            background: #fee2e2;
            color: #b91c1c;
        }
    </style>

    @if (session('status'))
        <div class="credit-management__alert" role="status">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M12 8v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
                <path d="M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
                <path d="M12 4c4.418 0 8 3.582 8 8s-3.582 8-8 8-8-3.582-8-8 3.582-8 8-8Z" stroke="currentColor"
                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <div class="credit-management__header">
        <h1>Gestão de créditos</h1>
        <p>Confira o consumo de créditos e status de pagamento do mês selecionado. Mês atual: <strong>{{ $monthLabel }}</strong></p>
    </div>

    <form method="GET" class="credit-management__filters">
        <label class="credit-management__select">
            <span>Filtrar por mês</span>
            <select name="month" onchange="this.form.submit()">
                @foreach ($monthOptions as $option)
                    <option value="{{ $option['key'] }}" @selected($option['key'] === $selectedMonthKey)>
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
        </label>
        <div class="credit-management__search">
            <span>Buscar por usuário</span>
            <div class="credit-management__search-field">
                <input type="search" name="search" value="{{ $searchQuery }}" placeholder="Nome, e-mail, status, créditos..."
                    class="credit-management__search-input">
                <button type="submit" class="credit-management__search-button">
                    Pesquisar
                </button>
                @if ($searchQuery !== '')
                    <a
                        href="{{ route('admin.payments.index', ['month' => $selectedMonthKey]) }}"
                        class="credit-management__search-clear"
                    >
                        Limpar
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-wrapper">
        <table aria-label="Lista de usuários e status de pagamento">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Créditos no mês</th>
                    <th>Status do pagamento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <span style="font-weight: 600; color: var(--text-strong);">{{ $user->name }}</span>
                                <span style="font-size: 13px; color: var(--text-muted);">{{ $user->email }}</span>
                                <span
                                    class="credit-management__user-status {{ $user->is_active ? 'credit-management__user-status--active' : 'credit-management__user-status--inactive' }}">
                                    {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $user->monthly_credits_used }}</strong>
                            <span style="font-size: 13px; color: var(--text-muted); display: block;">créditos utilizados</span>
                            @if (($user->monthly_amount_used ?? 0) > 0)
                                <span class="credit-management__amount">{{ $formatCurrency((float) $user->monthly_amount_used) }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->effective_payment_status === 'pending')
                                <span class="credit-management__status-pill credit-management__status-pill--pending">
                                    Pendente
                                </span>
                            @elseif ($user->effective_payment_status === 'paid')
                                <span class="credit-management__status-pill credit-management__status-pill--paid">
                                    Pago
                                </span>
                            @else
                                <span class="credit-management__status-pill credit-management__status-pill--neutral">
                                    Sem consumo
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="credit-management__actions">
                                @if ($user->has_pending_payment)
                                    <form method="POST"
                                        action="{{ route('admin.payments.mark-paid', ['user' => $user->id]) }}">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $selectedMonthKey }}">
                                        @if ($searchQuery !== '')
                                            <input type="hidden" name="search" value="{{ $searchQuery }}">
                                        @endif
                                        <button type="submit" class="credit-management__button credit-management__button--primary">
                                            Marcar como pago
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('admin.payments.deactivate', ['user' => $user->id]) }}"
                                        id="deactivate-form-{{ $user->id }}">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $selectedMonthKey }}">
                                        @if ($searchQuery !== '')
                                            <input type="hidden" name="search" value="{{ $searchQuery }}">
                                        @endif
                                        <button type="button"
                                            class="credit-management__button credit-management__button--danger"
                                            data-action="open-deactivate"
                                            data-form="deactivate-form-{{ $user->id }}"
                                            data-user-name="{{ $user->name }}">
                                            Inativar
                                        </button>
                                    </form>
                                @else
                                    @if ($user->effective_payment_status === 'paid')
                                        <span style="font-size: 13px; color: var(--text-muted);">Nenhuma ação necessária</span>
                                    @else
                                        <span style="font-size: 13px; color: var(--text-muted);">Sem consumo neste mês</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="credit-management__empty">
                                Nenhum usuário com movimentação para o mês selecionado.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="credit-management__modal-backdrop" data-modal="deactivate-confirm">
        <div class="credit-management__modal" role="dialog" aria-modal="true" aria-labelledby="deactivate-modal-title">
            <h2 id="deactivate-modal-title">Inativar usuário</h2>
            <p data-modal-text>Tem certeza que deseja inativar este usuário?</p>
            <div class="credit-management__modal-actions">
                <button type="button"
                    class="credit-management__modal-button credit-management__modal-button--secondary"
                    data-action="cancel-deactivate">
                    Cancelar
                </button>
                <button type="button"
                    class="credit-management__modal-button credit-management__modal-button--danger"
                    data-action="confirm-deactivate">
                    Inativar
                </button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modalBackdrop = document.querySelector('[data-modal="deactivate-confirm"]');
            if (!modalBackdrop) return;

            const modalText = modalBackdrop.querySelector('[data-modal-text]');
            let pendingForm = null;

            const openModal = (form, userName) => {
                pendingForm = form;
                if (modalText) {
                    modalText.textContent = `Tem certeza que deseja inativar ${userName}? Essa ação impede o acesso ao aplicativo até nova ativação.`;
                }
                modalBackdrop.classList.add('is-visible');
            };

            const closeModal = () => {
                pendingForm = null;
                modalBackdrop.classList.remove('is-visible');
            };

            document.querySelectorAll('[data-action="open-deactivate"]').forEach((button) => {
                button.addEventListener('click', () => {
                    const formId = button.getAttribute('data-form');
                    const userName = button.getAttribute('data-user-name') ?? 'este usuário';
                    const form = formId ? document.getElementById(formId) : null;
                    if (!form) return;
                    openModal(form, userName);
                });
            });

            modalBackdrop.addEventListener('click', (event) => {
                if (event.target === modalBackdrop) {
                    closeModal();
                }
            });

            modalBackdrop.querySelector('[data-action="cancel-deactivate"]')?.addEventListener('click', closeModal);
            modalBackdrop.querySelector('[data-action="confirm-deactivate"]')?.addEventListener('click', () => {
                if (pendingForm) {
                    pendingForm.submit();
                }
                closeModal();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modalBackdrop.classList.contains('is-visible')) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
