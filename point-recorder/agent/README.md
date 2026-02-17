# Agent (poll + replay) para processar pendências do MySQL

Este agente roda em um PC (normalmente Windows) e:

1) **A cada 5s**, busca 1 registro `pending` no MySQL (`placas_zero_km_requests`)  
2) Marca como `running` (com trava usando `placas_zero_km_runner_state`)  
3) Executa um **replay** de um template gravado (`record-template.js`) substituindo:
   - `cpf_cgc`
   - `nome`
   - `chassi`
4) Tira **print** quando o template mandar (evento `screenshot`)  
5) Atualiza o DB como `succeeded` ou `failed`

## Avisos importantes

- **Não coloque sua senha do banco em código**. Use `.env` (não versionado).
- Este projeto **não resolve CAPTCHA** nem deve ser usado para burlar bloqueios. Se o fluxo tiver captcha/2FA, trate manualmente.
- O replay depende de **mesma resolução/escala** da gravação (DPI/zoom do Windows).

## 1) Instalar dependências

```bash
cd point-recorder
npm install
```

## 2) Configurar `.env`

Crie `point-recorder/.env` baseado em `point-recorder/agent/.env.example`.

Se você gravou um template novo apenas para o fluxo pós-login, mantenha:

```
AGENT_LOGIN_TEMPLATE_PATH=recordings/login-antes-enter.json
AGENT_TEMPLATE_PATH=recordings/meu-template.json
```

Assim o agente roda primeiro o login e, após entrar, executa o template principal.

Se precisar estabilizar a tela entre os dois, use:

```
AGENT_BETWEEN_TEMPLATES_WAIT_MS=2000
```

## 3) Gravar o template (1 vez)

Abra o e-System na tela certa e grave o template:

```bash
npm run record:template
```

Durante a gravação:

- Clique nos campos e navegue normalmente
- Quando estiver no **primeiro campo** de CPF/CNPJ, pressione **F6**
- No campo Nome, **F7**
- No campo Chassi, **F8**
- No campo Senha, **F9** (opcional, recomendado quando precisa logar no sistema)
- Quando aparecer o modal final com as placas, pressione **F12** (marca ponto do print)

Pare com `Ctrl+C`. O arquivo padrão sai em `point-recorder/recordings/template.json`.

## 4) Rodar o agente (fica em loop)

```bash
npm run agent:poller
```

Ele:
- tenta “pegar” 1 pendência por vez;
- quando não tem nada, espera e tenta de novo.

Se o seu template tem esperas longas entre cliques, use no `.env`:

```
AGENT_MAX_DELAY_MS=0
```

Isso evita adiantar o replay.

Se você marcou `F9` no template, configure também:

```
AGENT_LOGIN_PASSWORD=sua_senha
```

Quando o slot `senha` (F9) é executado, o agente cola a senha e envia `ENTER` automaticamente para confirmar login.

Se o sistema demorar para carregar depois do login, configure uma espera extra:

```
AGENT_POST_LOGIN_WAIT_MS=10000
```

Se o template foi gravado para começar somente após login já concluído, use também:

```
AGENT_PRE_REPLAY_WAIT_MS=8000
```

### CPF x CNPJ no slot `cpf_cgc` (F6)

O replay trata automaticamente o documento em 3 campos:

- **CPF (11 dígitos)**: preenche `campo1=9 dígitos`, `campo2=vazio`, `campo3=2 dígitos`.
- **CNPJ (14 dígitos)**: preenche `campo1=8 dígitos`, `campo2=4 dígitos`, `campo3=2 dígitos`.

Use TAB na gravação para navegar entre os campos. O agente valida o tamanho e falha com erro claro se não for CPF/CNPJ válido.

## OCR (IA local)

O agente tenta rodar OCR na imagem e extrair:
- mensagem de erro (ex: “FICHA CADASTRAL JA EXISTENTE”)
- placas visíveis no modal

Configuração no `.env`:
- `AGENT_OCR_ENABLED=true`
- `AGENT_OCR_LANG=por`

Observação: na primeira execução o Tesseract pode baixar dados de idioma.

## Upload para o backend (recomendado)

Se você quer salvar a imagem no servidor e fazer o OCR no Laravel:

- `AGENT_UPLOAD_ENABLED=true`
- `AGENT_UPLOAD_URL=https://seu-dominio/api/public/placas-0km/screenshot`
- `AGENT_UPLOAD_API_KEY=...` (se configurado no backend)

Nesse modo, o agente envia o print e o backend processa o resto.

## Saída (prints)

Os prints vão para `point-recorder/screenshots/` (por padrão).

## Recorte do print (modal mais de perto)

Se quiser salvar a imagem já recortada (ao redor do último clique):

```
AGENT_SCREENSHOT_CROP_W=700
AGENT_SCREENSHOT_CROP_H=520
```

Dica: clique dentro do modal e pressione **F12** para garantir que o recorte pegue o conteúdo certo.

## Parar replay no screenshot

Para evitar cliques extras após capturar o modal, deixe:

```
AGENT_TEMPLATE_STOP_AT_SCREENSHOT=true
```

Assim o agente executa até o primeiro `screenshot` e encerra o replay daquele item.

## Logs

Por padrão o agente grava em `point-recorder/logs/agent.log`.

Configuração no `.env`:
- `AGENT_LOG_FILE=logs/agent.log`
- `AGENT_LOG_LEVEL=info`
- `AGENT_LOG_CONSOLE=true`
