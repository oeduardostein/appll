# E-CRV Token Refresh Automático

Script que combina **login no E-CRV** + **manutenção de token** em um único processo.

## O que faz

1. **Login inicial** no E-CRV SP usando automação de cliques
2. **Salva o JSESSIONID** no banco de dados MySQL
3. **Mantém a sessão ativa** com refresh periódico:
   - Verifica a sessão a cada 30 segundos
   - Recarrega a página a cada 5 minutos (configurável)
   - Faz login novo após 55 minutos (antes de expirar)
   - Salva o token atualizado no banco

## Como usar

### ⚠️ MUITO IMPORTANTE ⚠️

**NUNCA clique duas vezes no arquivo `.js` diretamente!**
O Windows tentará executar com Windows Script Host (WSH) que não suporta ES modules.

**Sempre use um destes arquivos para iniciar:**
- `START-ECRV-REFRESH.cmd` ⭐ **RECOMENDADO** (duplo clique)
- `run-ecrv-refresh.bat` (duplo clique)
- Ou via linha de comando

---

### Opção 1: Arquivo .cmd/.bat (Windows) - RECOMENDADO

**Duplo clique em:**
```
START-ECRV-REFRESH.cmd
```

Ou via linha de comando:
```bash
# Usar CPF/PIN padrão
START-ECRV-REFRESH.cmd

# CPF customizado
START-ECRV-REFRESH.cmd --cpf 12345678901 --pin 4321

# Intervalo de refresh customizado (minutos)
START-ECRV-REFRESH.cmd --interval 10
```

---

### Opção 2: NPM (Windows/Linux)

```bash
cd click-automation
npm run ecrv:refresh

# Com parâmetros
npm run ecrv:refresh -- --cpf 12345678901 --pin 4321 --interval 10
```

---

### Opção 3: Node direto (via terminal)

```bash
cd click-automation
node run-ecrv-with-refresh.js
```

## Parâmetros

| Parâmetro | Descrição | Default |
|-----------|-----------|---------|
| `--cpf` | CPF para login | 44922011811 |
| `--pin` | PIN para login | 1234 |
| `--interval` | Intervalo de refresh (minutos) | 5 |
| `--help, -h` | Mostra ajuda | - |

## Requisitos

- **Chrome** rodando com remote debugging na porta 9222
- **PowerShell** (Windows) - para automação de cliques
- **Node.js** com as dependências instaladas
- **Banco MySQL** configurado

## Diferença entre os scripts

| Script | Faz o quê? |
|--------|------------|
| `run-ecrv.js` | Só faz login **uma vez** e encerra |
| `run-ecrv-with-refresh.js` | Faz login **e mantém token ativo** continuamente |
| `token-refresh.mjs` (point-recorder) | Usa Playwright + RobotJS (mais complexo) |
| `token-updater-ecrv.mjs` (point-recorder) | Usa Puppeteer (mais leve) |

## Exemplo de saída

```
[2025-03-16T10:00:00.000Z] [INFO] Fazendo login inicial no E-CRV...
[2025-03-16T10:00:15.000Z] [INFO] Automação de login concluída com sucesso
[2025-03-16T10:00:16.000Z] [INFO] Conectado ao Chrome!
[2025-03-16T10:00:17.000Z] [INFO] Token inicial salvo: ABC123XYZ4...
[2025-03-16T10:00:17.000Z] [INFO] Iniciando loop de manutenção de sessão...
[2025-03-16T10:00:47.000Z] [INFO] Verificando sessão... (idade: 30s)
[2025-03-16T10:00:47.000Z] [INFO] JSESSIONID salvo: ABC123XYZ4...
[2025-03-16T10:05:17.000Z] [INFO] Recarregando página para manter sessão ativa...
...
```

## Parar o script

Pressione **Ctrl+C** para encerrar gracefulmente.
