# Redirecionamento correto com `.htaccess`

Este projeto utiliza Apache para servir o backend Laravel localizado em `backend/public`. O arquivo `.htaccess` (em `backend/public/.htaccess`) controla todo o roteamento HTTP quando o servidor recebe uma requisição. O objetivo deste documento é explicar como o redirecionamento funciona e o que precisa ser configurado para reproduzir o mesmo comportamento em outro projeto ou servidor.

## Pré-requisitos no Apache

1. **Habilitar `mod_rewrite`** – o conjunto de regras depende desse módulo (`a2enmod rewrite` em distros Debian/Ubuntu; lembre-se de reiniciar o Apache).
2. **Permitir overrides** – na configuração do VirtualHost, defina `AllowOverride All` para o diretório público do Laravel. Exemplo:

   ```apache
   <Directory /var/www/appll/backend/public>
       AllowOverride All
       Require all granted
   </Directory>
   ```

3. **Apontar a raiz pública** – `DocumentRoot` deve ser `backend/public`, não o diretório raiz do projeto, para evitar expor arquivos sensíveis.

## Regras principais do `.htaccess`

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Explicação das seções

- **Proteções iniciais**: `Options -MultiViews -Indexes` impede que o Apache tente negociar conteúdo por nome parecido ou liste diretórios vazios.
- **`RewriteEngine On`**: ativa o mecanismo de reescrita do Apache e precisa vir antes das regras.
- **Cabeçalhos especiais**: as duas primeiras regras copiam os cabeçalhos `Authorization` e `X-XSRF-Token` para variáveis de ambiente. Isso garante que o PHP (Laravel) consiga ler esses valores mesmo quando o servidor está por trás de proxies ou gateways que perdem os cabeçalhos originais.
- **Remoção de barra final duplicada**: quando a URL termina com `/` e não é um diretório físico, a regra `RewriteRule ^ %1 [L,R=301]` redireciona para a versão sem a barra final. Isso evita conteúdo duplicado e melhora o SEO.
- **Front controller**: se a rota solicitada não corresponde a um arquivo (`!-f`) nem a um diretório (`!-d`), tudo é encaminhado para `index.php`. O Laravel interpreta a URL e decide qual rota deve atender o usuário.

## Como replicar em outro código

1. Copie o arquivo `.htaccess` para o diretório `public` (ou equivalente) do outro projeto baseado em Laravel ou PHP com front controller.
2. Ajuste o `DocumentRoot` do novo VirtualHost para apontar para o diretório onde o `.htaccess` ficará.
3. Confirme se o servidor possui os mesmos módulos habilitados (`mod_rewrite`, `mod_negotiation`).
4. Se precisar de cabeçalhos adicionais (por exemplo, autenticação customizada), basta seguir o padrão das regras existentes para expor os valores a partir do Apache.
5. Teste acessando URLs inexistentes, rotas válidas e rotas com barra final para garantir que a reescrita esteja ativa. O comando `curl -I https://seu-dominio.dev/rota/` deve retornar `301` para `https://seu-dominio.dev/rota`.

Com essas instruções você consegue entender e reproduzir exatamente o fluxo de redirecionamento implementado neste projeto, garantindo que futuros ajustes em outro código mantenham o mesmo comportamento.
