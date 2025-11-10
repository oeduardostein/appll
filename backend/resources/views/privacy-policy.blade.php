<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidade | Appll</title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f5f7fb;
            color: #111827;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 48px 24px 72px;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: clamp(2rem, 4vw, 2.75rem);
            margin-bottom: 12px;
            color: #0f172a;
        }

        h2 {
            font-size: 1.5rem;
            margin-top: 36px;
            margin-bottom: 12px;
            color: #111827;
        }

        p,
        li {
            line-height: 1.6;
            color: #1f2937;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 15px 45px rgba(15, 23, 42, 0.08);
        }

        ul {
            padding-left: 20px;
        }

        footer {
            margin-top: 48px;
            text-align: center;
            font-size: 0.9rem;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Política de Privacidade</h1>
            <p>Atualizada em {{ now()->format('d/m/Y') }}</p>
        </header>

        <section class="card">
            <p>
                Esta Política de Privacidade descreve como o Appll coleta, utiliza, armazena e protege os dados
                pessoais de clientes, parceiros e visitantes da nossa plataforma e do aplicativo móvel. Ao utilizar
                nossos serviços você concorda com as práticas descritas abaixo.
            </p>

            <h2>1. Dados que coletamos</h2>
            <ul>
                <li>Informações cadastrais como nome, email, CPF/CNPJ e dados de contato.</li>
                <li>Dados de uso do aplicativo, logs de acesso e preferências de navegação.</li>
                <li>Documentos e comprovantes necessários para execução dos serviços contratados.</li>
            </ul>

            <h2>2. Como utilizamos os dados</h2>
            <ul>
                <li>Para criar e gerenciar contas de acesso ao Appll.</li>
                <li>Para prestar os serviços solicitados, como consultas, emissões e acompanhamento de processos.</li>
                <li>Para comunicar atualizações, alertas operacionais e ofertas relevantes.</li>
                <li>Para cumprir obrigações legais e regulatórias.</li>
            </ul>

            <h2>3. Compartilhamento</h2>
            <p>
                Os dados podem ser compartilhados com órgãos públicos, parceiros e fornecedores que nos auxiliam a
                executar as atividades do Appll, sempre observando contratos de confidencialidade e a legislação
                aplicável.
            </p>

            <h2>4. Direitos dos titulares</h2>
            <p>
                Você pode solicitar acesso, correção, anonimização ou exclusão dos seus dados pessoais, bem como
                obter informações sobre o uso que fazemos deles. Entre em contato pelos canais abaixo para exercer os
                seus direitos.
            </p>

            <h2>5. Segurança e armazenamento</h2>
            <p>
                Adotamos controles técnicos e administrativos para proteger suas informações contra acessos
                não autorizados, perda, alteração ou destruição. Seus dados são armazenados em ambientes controlados
                e com acesso restrito.
            </p>

            <h2>6. Atualizações desta política</h2>
            <p>
                Podemos atualizar esta Política de Privacidade a qualquer momento para refletir melhorias no serviço,
                exigências legais ou mudanças operacionais. A versão vigente estará sempre disponível nesta página.
            </p>

            <h2>7. Contato</h2>
            <p>
                Em caso de dúvidas sobre esta política ou sobre o tratamento dos seus dados pessoais, envie um email
                para <a href="mailto:contato@appll.com.br">contato@appll.com.br</a>.
            </p>
        </section>

        <footer>
            &copy; {{ now()->year }} Appll Despachante. Todos os direitos reservados.
        </footer>
    </div>
</body>

</html>
