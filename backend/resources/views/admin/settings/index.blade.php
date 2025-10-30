@extends('admin.layouts.app')

@section('content')
    <style>
        .settings-header {
            margin-bottom: 32px;
        }

        .settings-header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .settings-header p {
            margin: 8px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }

        .settings-grid {
            display: grid;
            gap: 24px;
        }

        @media (min-width: 992px) {
            .settings-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .settings-card {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 28px 32px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .settings-card h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .settings-card p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .admin-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .admin-field label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .admin-field input {
            border: 1px solid #d0d9e3;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            background: var(--surface);
            color: var(--text-default);
        }

        .admin-field input:focus {
            outline: 2px solid rgba(11, 78, 162, 0.18);
            border-color: var(--brand-primary);
        }

        .settings-actions {
            display: flex;
            justify-content: flex-end;
        }

        .admin-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .admin-button--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.22);
        }

        .admin-button--primary:hover {
            transform: translateY(-1px);
        }

        .settings-feedback {
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 14px;
            font-weight: 600;
        }

        .settings-feedback--success {
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
        }

        .settings-errors {
            margin: 0;
            padding-left: 18px;
            color: #b91c1c;
            font-size: 13px;
        }
    </style>

    <header class="settings-header">
        <h1>Configurações</h1>
        <p>Gerencie informações sensíveis da sua conta administrativa.</p>
    </header>

    @if (session('status'))
        <div class="settings-feedback settings-feedback--success" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <ul class="settings-errors" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <section class="settings-grid">
        <form method="POST" action="{{ route('admin.settings.password') }}" class="settings-card">
            @csrf
            <div>
                <h2>Alterar senha</h2>
                <p>Para segurança, confirme a senha atual antes de definir uma nova senha de acesso.</p>
            </div>

            <div class="admin-field">
                <label for="current_password">Senha atual</label>
                <input
                    id="current_password"
                    type="password"
                    name="current_password"
                    autocomplete="current-password"
                    required
                />
            </div>

            <div class="admin-field">
                <label for="password">Nova senha</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                />
            </div>

            <div class="admin-field">
                <label for="password_confirmation">Confirmar nova senha</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    minlength="8"
                    required
                />
            </div>

            <div class="settings-actions">
                <button type="submit" class="admin-button admin-button--primary">
                    Atualizar senha
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.settings.api-key') }}" class="settings-card">
            @csrf
            <div>
                <h2>Chave de API</h2>
                <p>Utilize uma chave única para integrações externas e serviços automatizados.</p>
            </div>

            <div class="admin-field">
                <label for="api_key">Chave de API</label>
                <input
                    id="api_key"
                    type="text"
                    name="api_key"
                    value="{{ old('api_key', $admin->api_key) }}"
                    placeholder="Informe sua chave ou gere uma nova"
                    autocomplete="off"
                    required
                />
            </div>

            <div class="settings-actions">
                <button type="submit" class="admin-button admin-button--primary">
                    Salvar chave
                </button>
            </div>
        </form>
    </section>
@endsection
