@extends('admin.layouts.app')

@php
    $fallbackValue = config('credit-values.fallback', 1.0);
    $checkedPermissions = $user->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
    $selectedPermissions = collect(old('permissions', $checkedPermissions))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->all();
@endphp

@section('content')
    <style>
        .edit-user-page {
            max-width: 1080px;
            margin: 0 auto;
        }

        .edit-user-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 28px;
        }

        .edit-user-header h1 {
            margin: 0;
            font-size: 32px;
            color: var(--text-strong);
        }

        .edit-user-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--brand-primary);
            font-weight: 600;
            font-size: 14px;
        }

        .edit-user-card {
            background: #fff;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.08);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .admin-field label {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: block;
        }

        .admin-field input,
        .admin-field select {
            width: 100%;
            border-radius: 14px;
            border: 1px solid #d7deeb;
            padding: 12px 16px;
            font-size: 15px;
            color: var(--text-default);
            background: #f9fbff;
        }

        .permission-section {
            margin-top: 36px;
        }

        .permission-section h2 {
            font-size: 20px;
            margin-bottom: 6px;
        }

        .permission-section p {
            margin: 0 0 20px;
            color: var(--text-muted);
        }

        .permission-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .permission-row {
            border: 1px solid #e5eaf3;
            border-radius: 18px;
            padding: 18px 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: flex-start;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .permission-row.is-active {
            border-color: var(--brand-primary);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .permission-row__toggle {
            flex: 1;
            min-width: 220px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: var(--text-strong);
            cursor: pointer;
        }

        .permission-row__toggle input {
            width: 18px;
            height: 18px;
        }

        .permission-row__price {
            min-width: 220px;
            flex: 1;
        }

        .permission-row__price span {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .price-field {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #d7deeb;
            border-radius: 12px;
            padding: 8px 12px;
            background: #fff;
        }

        .price-field strong {
            color: var(--text-muted);
            font-size: 14px;
        }

        .price-field input {
            border: none;
            background: transparent;
            font-size: 15px;
            width: 120px;
            padding: 0;
        }

        .permission-row__price small {
            display: block;
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .form-actions {
            margin-top: 32px;
            display: flex;
            justify-content: flex-end;
            gap: 14px;
            flex-wrap: wrap;
        }

        .admin-button {
            border: none;
            border-radius: 999px;
            padding: 12px 22px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
        }

        .admin-button--ghost {
            background: #edf2ff;
            color: var(--text-default);
        }

        .admin-button--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 15px 30px rgba(11, 78, 162, 0.25);
        }

        .form-alert {
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 20px;
            background: #fef2f2;
            color: #b91c1c;
        }

        .form-alert ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }
    </style>

    <div class="edit-user-page">
        <a href="{{ route('admin.clients.index') }}" class="edit-user-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            Voltar para a lista de clientes
        </a>

        <div class="edit-user-header">
            <div>
                <h1>Editar usuário</h1>
                <p style="margin: 6px 0 0; color: var(--text-muted);">{{ $user->email }} · Cadastrado em {{ $user->created_at?->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="edit-user-card">
            @if ($errors->any())
                <div class="form-alert">
                    <strong>Corrija os campos destacados:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_to" value="{{ route('admin.clients.show', $user) }}">

                <div class="form-grid">
                    <div class="admin-field">
                        <label for="edit-name">Nome completo</label>
                        <input id="edit-name" name="name" type="text" value="{{ old('name', $user->name) }}" required />
                    </div>

                    <div class="admin-field">
                        <label for="edit-email">E-mail</label>
                        <input id="edit-email" name="email" type="email" value="{{ old('email', $user->email) }}" required />
                    </div>

                    <div class="admin-field">
                        <label for="edit-password">Senha</label>
                        <input id="edit-password" name="password" type="password" placeholder="Deixe em branco para manter" />
                    </div>

                    <div class="admin-field">
                        <label for="edit-status">Status</label>
                        <select id="edit-status" name="is_active">
                            <option value="1" @selected(old('is_active', $user->is_active ? '1' : '0') === '1')>Ativo</option>
                            <option value="0" @selected(old('is_active', $user->is_active ? '1' : '0') === '0')>Inativo</option>
                        </select>
                    </div>
                </div>

                <section class="permission-section">
                    <h2>Permissões de acesso e valores</h2>
                    <p>Habilite as ferramentas que o cliente pode usar e defina o valor cobrado por crédito em cada uma delas.</p>

                    <div class="permission-list">
                        @foreach ($permissions as $permission)
                            @php
                                $isChecked = in_array($permission->id, $selectedPermissions, true);
                                $currentValue = old(
                                    'permission_credit_values.' . $permission->id,
                                    $permissionValues[$permission->id] ?? $permission->default_credit_value ?? $fallbackValue,
                                );
                                $formattedValue = number_format((float) ($currentValue ?? $fallbackValue), 2, '.', '');
                                $defaultLabel = number_format((float) ($permission->default_credit_value ?? $fallbackValue), 2, ',', '.');
                            @endphp
                            <div class="permission-row {{ $isChecked ? 'is-active' : '' }}" data-permission-row>
                                <label class="permission-row__toggle">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                        data-permission-checkbox @checked($isChecked) />
                                    <span>{{ $permission->name }}</span>
                                </label>
                                <div class="permission-row__price">
                                    <span>Valor por crédito</span>
                                    <div class="price-field">
                                        <strong>R$</strong>
                                        <input type="number" min="0" step="0.01"
                                            name="permission_credit_values[{{ $permission->id }}]"
                                            value="{{ $formattedValue }}" data-permission-price
                                            {{ $isChecked ? '' : 'disabled' }} />
                                    </div>
                                    <small>Valor padrão: R${{ $defaultLabel }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="form-actions">
                    <a href="{{ route('admin.clients.show', $user) }}" class="admin-button admin-button--ghost">Cancelar</a>
                    <button type="submit" class="admin-button admin-button--primary">Atualizar usuário</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-permission-checkbox]').forEach((checkbox) => {
            const row = checkbox.closest('[data-permission-row]');
            const input = row ? row.querySelector('[data-permission-price]') : null;

            const updateState = () => {
                if (!row || !input) {
                    return;
                }

                row.classList.toggle('is-active', checkbox.checked);
                input.disabled = !checkbox.checked;
            };

            checkbox.addEventListener('change', updateState);
            updateState();
        });
    </script>
@endsection
