<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suporte | LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top, #e0f2fe 0%, #f8fafc 55%);
            color: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 32px 16px;
        }

        .card {
            width: min(440px, 100%);
            background: #fff;
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.12);
            text-align: center;
        }

        h1 {
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            margin-bottom: 16px;
        }

        p {
            margin: 0 0 24px;
            line-height: 1.6;
            color: #475569;
        }

        .contact-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 20px;
        }

        .label {
            font-size: 0.95rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
        }

        .value a {
            color: inherit;
            text-decoration: none;
        }

        .value a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <section class="card">
        <h1>Suporte LL Grupo</h1>
        <p>Precisa de ajuda? Entre em contato com a nossa equipe pelos canais oficiais abaixo.</p>

        <div class="contact-item">
            <span class="label">Telefone</span>
            <span class="value">
                <a href="tel:+5513997731533">+55 13 99773-1533</a>
            </span>
        </div>

        <div class="contact-item">
            <span class="label">E-mail</span>
            <span class="value">
                <a href="mailto:suporte@llgrupo.com">suporte@llgrupo.com</a>
            </span>
        </div>
    </section>
</body>

</html>
