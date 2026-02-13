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

## 3) Gravar o template (1 vez)

Abra o e-System na tela certa e grave o template:

```bash
npm run record:template
```

Durante a gravação:

- Clique nos campos e navegue normalmente
- Quando estiver com o cursor no campo do CPF/CNPJ, pressione **F6**
- No campo Nome, **F7**
- No campo Chassi, **F8**
- Quando aparecer o modal final com as placas, pressione **F12** (marca ponto do print)

Pare com `Ctrl+C`. O arquivo padrão sai em `point-recorder/recordings/template.json`.

## 4) Rodar o agente (fica em loop)

```bash
npm run agent:poller
```

Ele:
- tenta “pegar” 1 pendência por vez;
- quando não tem nada, espera e tenta de novo.

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
