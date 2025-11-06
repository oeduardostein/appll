<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }} · Recuperar senha</title>
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

            .card-wrapper {
                width: min(480px, 100%);
            }

            .card {
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
                font-size: 30px;
                font-weight: 600;
            }

            header p {
                margin: 0;
                color: var(--text-light);
                font-size: 15px;
                line-height: 1.4;
            }

            form {
                margin-top: 32px;
                display: grid;
                gap: 24px;
            }

            label {
                display: block;
                color: var(--text-strong);
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 8px;
            }

            input[type='email'] {
                width: 100%;
                padding: 14px 16px;
                border: 1px solid #d1d5db;
                border-radius: 14px;
                background: var(--surface-muted);
                color: var(--text-regular);
                font-size: 15px;
                transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
            }

            input[type='email']:focus {
                outline: none;
                border-color: var(--brand-primary);
                background: var(--surface);
                box-shadow: 0 0 0 4px rgba(11, 78, 162, 0.18);
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

            .back-link {
                margin-top: 24px;
                text-align: center;
            }

            .back-link a {
                font-size: 14px;
                color: #fff;
                text-decoration: none;
                font-weight: 500;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                height: 100px;
                width: 100px;
                margin-bottom: 24px;
                border-radius: 999px;
                background: #fff;
                border: 6px solid rgba(255, 255, 255, 0.65);
                box-shadow: 0 18px 40px rgba(3, 24, 56, 0.35);
                overflow: hidden;
            }

            .brand img {
                width: 82%;
                height: 82%;
                object-fit: contain;
            }

            @media (max-width: 640px) {
                body {
                    padding: 24px 12px;
                }

                .card {
                    padding: 32px 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class="card-wrapper">
            <div class="brand">
                <img src="{{ asset('/backend/public/images/logoLL.png') }}" alt="Marca Grupo LL" />
            </div>

            <div class="card">
                <header>
                    <h1>Recuperar senha</h1>
                    <p>Informe o e-mail cadastrado para receber o código de recuperação.</p>
                </header>

                @if ($errors->any())
                    <div style="
                        background: #fee2e2;
                        color: #b91c1c;
                        padding: 12px 16px;
                        border-radius: 12px;
                        font-size: 14px;
                        font-weight: 500;
                        margin-bottom: 16px;
                    ">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if (session('status'))
                    <div style="
                        background: #ecfdf5;
                        color: #047857;
                        padding: 12px 16px;
                        border-radius: 12px;
                        font-size: 14px;
                        font-weight: 500;
                        margin-bottom: 16px;
                    ">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.password.email') }}">
                    @csrf

                    <div>
                        <label for="email">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="seuemail@exemplo.com"
                            autocomplete="email"
                            value="{{ old('email') }}"
                            required
                        />
                    </div>

                    <button type="submit">Enviar código</button>
                </form>
            </div>

            <div class="back-link">
                <a href="{{ route('admin.login') }}">Voltar para o login</a>
            </div>
        </div>
    </body>
</html>
