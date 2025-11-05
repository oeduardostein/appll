@extends('admin.layouts.app')

@section('content')
    @php
        $monthLabel = ucfirst($selectedMonth->translatedFormat('F \\d\\e Y'));
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
                        </td>
                        <td>
                            @if ((bool) $user->has_paid)
                                <span class="credit-management__status-pill credit-management__status-pill--paid">
                                    Pago
                                </span>
                            @else
                                <span class="credit-management__status-pill credit-management__status-pill--pending">
                                    Pendente
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="credit-management__actions">
                                @if (! (bool) $user->has_paid)
                                    <form method="POST"
                                        action="{{ route('admin.payments.mark-paid', ['user' => $user->id]) }}">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $selectedMonthKey }}">
                                        <button type="submit" class="credit-management__button credit-management__button--primary">
                                            Marcar como pago
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('admin.payments.deactivate', ['user' => $user->id]) }}"
                                        onsubmit="return confirm('Tem certeza que deseja inativar este usuário?');">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $selectedMonthKey }}">
                                        <button type="submit" class="credit-management__button credit-management__button--danger">
                                            Inativar
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 13px; color: var(--text-muted);">Nenhuma ação necessária</span>
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
@endsection
