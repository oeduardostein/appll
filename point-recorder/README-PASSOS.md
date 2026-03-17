# Scripts Separados - Fluxo Passo a Passo

Execute na ordem ou use o menu principal!

## 🚀 Menu Principal

**Duplo clique em: `0-iniciar-tudo.bat`**

Menu interativo com opções:
- **[1]** Iniciar tudo (Completo)
- **[2]** Só inicialização (dependências + logins)
- **[3]** Só Poller + Token Refresh
- **[0]** Sair

## 📋 Scripts Individuais

| Arquivo | O que faz |
|---------|-----------|
| **`0-iniciar-tudo.bat`** | 🎯 Menu com todas as opções |
| **`1-instalar-dependencias.bat`** | 📦 `npm install` nas duas pastas |
| **`2-login-esystem.bat`** | 💻 Abre e-System E executa login automático (senha + 2 cliques) |
| **`3-login-ecrv.bat`** | 🌐 Abre e faz login no E-CRV |
| **`4-iniciar-poller-e-refresh.bat`** | 🔄 Poller + Token Refresh juntos |

## 🔄 Fluxo Completo

```
┌─────────────────────────────────────────────────────────────┐
│  0-iniciar-tudo.bat (Menu Principal)                        │
└─────────────────────────────────────────────────────────────┘
                            │
         ┌──────────────────┼──────────────────┐
         │                  │                  │
         ▼                  ▼                  ▼
    [1] TUDO          [2] INICIALIZAÇÃO    [3] POLLER
         │                  │                  │
         ▼                  ▼                  ▼
    ┌─────────┐       ┌─────────┐       ┌─────────────────┐
    │ 1. npm  │       │ 1. npm  │       │ Token Refresh  │
    │ install │       │ install │       │ +               │
    ├─────────┤       ├─────────┤       │ Agent Poller    │
    │ 2. e-   │       │ 2. e-   │       └─────────────────┘
    │ System  │       │ System  │
    ├─────────┤       ├─────────┤
    │ 3. E-   │       │ 3. E-   │
    │ CRV     │       │ CRV     │
    ├─────────┤       └─────────┘
    │ 4. Poller│         │
    │ Refresh  │         │
    └─────────┘         ▼
                 Depois execute [3]
```

## 📖 Como usar

### Opção 1 - Menu (Recomendado)
```
Duplo clique em: 0-iniciar-tudo.bat
Escolha a opção [1] para tudo
```

### Opção 2 - Passo a passo
```
1. Duplo clique em: 1-instalar-dependencias.bat
2. Depois: 2-login-esystem.bat
3. Depois: 3-login-ecrv.bat
4. Depois: 4-iniciar-poller-e-refresh.bat
```

### Opção 3 - Uso diário
```
Após primeira configuração, use apenas:
- Opção [3] do menu (Poller + Token Refresh)
```

## ℹ️ Detalhes Importantes

### Login do e-System (Script 2)
O script `2-login-esystem.bat` agora faz:
1. **Abre o e-System** (aplicativo)
2. **Executa login automático** usando o template `meu-template-login-senha.json`:
   - Digita a senha "ll"
   - Pressiona Enter
   - Executa 2 cliques pós-login (para fechar dialogs/avisos)

O login usa o template definido em `AGENT_LOGIN_TEMPLATE_PATH` (no .env) e a senha em `AGENT_LOGIN_PASSWORD`.

## 🔧 Configurações

### Alterar CPF/PIN do E-CRV
Edite os arquivos `3-login-ecrv.bat` e `4-iniciar-poller-e-refresh.bat`:
```bat
set "ECRV_CPF=44922011811"
set "ECRV_PIN=1234"
```

### Alterar caminho do e-System
Edite `2-login-esystem.bat`:
```bat
set "ESYSTEM_EXE_PATH=C:\SH Sistemas\System Desp SX\eSystemDesp.exe"
```

### Alterar intervalo do Token Refresh
Edite `4-iniciar-poller-e-refresh.bat`:
```bat
set "TOKEN_REFRESH_INTERVAL=5"
```

## ⚠️ Troubleshooting

### Erro: "Node.js não encontrado"
- Instale em: https://nodejs.org/

### Erro: "Click-automation não encontrado"
- Verifique: `Desktop\teste\click-automation`

### Erro: "e-System não encontrado"
- Edite caminho em `2-login-esystem.bat`

### Login no E-CRV falha
- Feche o Chrome antes
- Verifique `e-crv-flow.json` no click-automation

## 📌 Logs

- **Token Refresh**: `%TEMP%\token-refresh-log.txt`
- Para ver o log: `type %TEMP%\token-refresh-log.txt`
