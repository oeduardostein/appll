<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }} · Admin</title>
        <style>
            :root {
                --brand-primary: #0b4ea2;
                --brand-primary-hover: #0a428c;
                --surface: #ffffff;
                --surface-muted: #f5f7fb;
                --text-strong: #12263a;
                --text-regular: #4b5563;
                --text-light: #6b7280;
                font-family: 'Instrument Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background-color: var(--brand-primary);
                color: var(--text-regular);
                font-family: inherit;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 32px 16px;
            }

            .login-wrapper {
                width: min(480px, 100%);
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: left;
            }

            .login-card {
                width: 100%;
                background-color: var(--surface);
                border-radius: 24px;
                padding: 48px;
                box-shadow:
                    0 30px 50px rgba(4, 26, 55, 0.35),
                    0 1px 0 rgba(255, 255, 255, 0.15);
            }

            header h1 {
                margin: 0 0 8px;
                color: var(--text-strong);
                font-size: 32px;
                font-weight: 600;
            }

            header p {
                margin: 0;
                color: var(--text-light);
                font-size: 14px;
            }

            form {
                display: grid;
                gap: 24px;
                margin-top: 32px;
            }

            label {
                display: block;
                color: var(--text-strong);
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 8px;
            }

            input[type='email'],
            input[type='password'] {
                width: 100%;
                padding: 14px 16px;
                border: 1px solid #d1d5db;
                border-radius: 14px;
                background: var(--surface-muted);
                color: var(--text-regular);
                font-size: 15px;
                transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
            }

            input[type='email']:focus,
            input[type='password']:focus {
                outline: none;
                border-color: var(--brand-primary);
                background: var(--surface);
                box-shadow: 0 0 0 4px rgba(11, 78, 162, 0.18);
            }

            .field-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .field-row a {
                font-size: 14px;
                color: var(--brand-primary);
                text-decoration: none;
                font-weight: 500;
                transition: color 160ms ease;
            }

            .field-row a:hover,
            .field-row a:focus {
                color: var(--brand-primary-hover);
            }

            .remember {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 14px;
                color: var(--text-light);
            }

            .remember input[type='checkbox'] {
                width: 18px;
                height: 18px;
                border-radius: 6px;
                border: 1px solid #cbd5f5;
                accent-color: var(--brand-primary);
            }

            button[type='submit'] {
                cursor: pointer;
                padding: 16px;
                width: 100%;
                background: var(--brand-primary);
                color: #fff;
                border: none;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 600;
                letter-spacing: 0.02em;
                transition: background-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
                box-shadow: 0 10px 25px rgba(3, 24, 56, 0.35);
            }

            button[type='submit']:hover,
            button[type='submit']:focus {
                background: var(--brand-primary-hover);
                transform: translateY(-1px);
            }

            .brand {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                height: 120px;
                width: 120px;
                margin-bottom: 32px;
                border-radius: 999px;
                background: #fff;
                border: 6px solid rgba(255, 255, 255, 0.65);
                box-shadow: 0 18px 40px rgba(3, 24, 56, 0.35);
                overflow: hidden;
            }

            .brand img {
                width: 86%;
                height: 86%;
                object-fit: contain;
            }

            @media (max-width: 640px) {
                body {
                    padding: 24px 12px;
                }

                .login-card {
                    padding: 32px 24px;
                }

                header h1 {
                    font-size: 28px;
                }

                form {
                    gap: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-wrapper">
            <span class="brand">
                <img src="{{ asset('images/logoLL.png') }}" alt="Marca Grupo LL" />
            </span>

            <div class="login-card">
                <header>
                    <h1>Entrar</h1>
                    <p>Entre com suas credenciais para acessar o painel</p>
                </header>

                <form method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf

                    @if ($errors->any())
                        <div style="
                            background: #fee2e2;
                            color: #b91c1c;
                            padding: 12px 16px;
                            border-radius: 12px;
                            font-size: 14px;
                            font-weight: 500;
                        ">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <label for="email">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="Digite seu email"
                            autocomplete="email"
                            value="{{ old('email') }}"
                            required
                        />
                    </div>

                    <div>
                        <div class="field-row">
                            <label for="password">Senha</label>
                            <a href="#">Esqueceu a senha?</a>
                        </div>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        />
                    </div>

                    <label class="remember">
                        <input type="checkbox" name="remember" />
                        Lembrar de mim
                    </label>

                    <button type="submit">Entrar</button>
                </form>
            </div>
        </div>
    </body>
</html>
