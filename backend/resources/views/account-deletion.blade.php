<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Excluir Conta | LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf2fb;
            color: #0f172a;
        }

        .card {
            width: min(420px, 90vw);
            background: #fff;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.75rem;
        }

        p {
            margin: 0 0 24px;
            line-height: 1.5;
            color: #475569;
        }

        label {
            display: block;
            font-size: 0.95rem;
            margin-bottom: 6px;
            font-weight: 600;
            color: #0f172a;
        }

        input {
            width: 100%;
            border: 1px solid #cbd5f5;
            border-radius: 10px;
            padding: 12px;
            font-size: 1rem;
            margin-bottom: 16px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        button {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #047857;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
        }

        .input-error {
            color: #b91c1c;
            font-size: 0.85rem;
            margin-top: -10px;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <main class="card">
        <h1>Excluir conta</h1>
        <p>Informe suas credenciais para excluir sua conta do LL Despachante. Esta ação é irreversível.</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('account-deletion.submit') }}">
            @csrf
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                autofocus>

            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">Excluir minha conta</button>
        </form>
    </main>
</body>

</html>
