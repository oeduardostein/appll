<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chave de segurança</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
<div style="max-width:560px;margin:0 auto;padding:24px;">
    <div style="background:#ffffff;border-radius:16px;padding:24px;border:1px solid #e2e8f0;">
        <h1 style="margin:0 0 12px 0;font-size:18px;color:#0f172a;">Chave de segurança</h1>
        <p style="margin:0 0 16px 0;font-size:14px;line-height:1.6;color:#334155;">
            Para concluir o login, use a chave abaixo:
        </p>

        <div style="font-size:28px;letter-spacing:4px;font-weight:700;color:#0047ab;background:#f1f5f9;border-radius:12px;padding:14px 16px;text-align:center;">
            {{ $securityKey }}
        </div>

        <p style="margin:16px 0 0 0;font-size:13px;line-height:1.6;color:#64748b;">
            Esta chave expira em {{ $expiresAt->format('d/m/Y H:i') }}.
            Se você não solicitou este login, ignore este e-mail.
        </p>
    </div>

    <p style="margin:16px 0 0 0;font-size:12px;color:#94a3b8;text-align:center;">
        LL Despachante
    </p>
</div>
</body>
</html>

