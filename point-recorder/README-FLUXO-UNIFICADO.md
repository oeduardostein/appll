# FLUXO UNIFICADO - E-SYSTEM + POLLER + E-CRV + TOKEN REFRESH

Script único que executa **todos os processos** necessários para o sistema funcionar.

## O que faz

Quando você executa `rodar-fluxo-agente.bat`, ele:

1. **Instala dependências** do point-recorder e click-automation
2. **Login no e-System** - Abre e traz para frente
3. **Login no E-CRV** - Faz login inicial via PowerShell
4. **Inicia Agent Poller** - Fica aguardando consultas de placas (primeiro plano)
5. **Inicia Token Refresh** - Mantém o JSESSIONID do E-CRV atualizado (background)

## Como usar

### No cliente (Windows)

```
1. Coloque as pastas em:
   Desktop\teste\point-recorder
   Desktop\teste\click-automation

2. Duplo clique em:
   Desktop\teste\point-recorder\rodar-fluxo-agente.bat
```

### Ajustar configurações

Edite o `rodar-fluxo-agente.bat` e altere:

```bat
REM Pastas
set "POINT_RECORDER_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
set "CLICK_AUTOMATION_DIR=%USERPROFILE%\Desktop\test\click-automation"

REM e-System
set "ESYSTEM_EXE_PATH=C:\SH Sistemas\System Desp SX\eSystemDesp.exe"

REM E-CRV
set "ECRV_CPF=44922011811"
set "ECRV_PIN=1234"

REM Token Refresh
set "TOKEN_REFRESH_INTERVAL=5"
```

## Fluxo de execução

```
┌─────────────────────────────────────────────────────────────┐
│  rodar-fluxo-agente.bat                                     │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │  1. npm install (point-recorder)     │
        │  2. npm install (click-automation)   │
        └──────────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │  3. Abrir e-System                   │
        └──────────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │  4. npm run ecrv (login inicial)     │
        └──────────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │  5. Agent Poller (primeiro plano)    │  ◄─── VOCÊ TRABALHA AQUI
        │     - Aguarda consultas de placas    │      (consulta placas)
        │     - Processa solicitações          │
        └──────────────────────────────────────┘
                           │
        ┌──────────────────────────────────────┐
        │  6. Token Refresh (background)       │
        │     - Recarrega página a cada 5min   │
        │     - Salva JSESSIONID no banco      │
        │     - Faz login novo a cada 55min    │
        └──────────────────────────────────────┘
```

## Scripts disponíveis

| Script | O que faz |
|--------|-----------|
| `rodar-fluxo-agente.bat` | ⭐ **Executa tudo** - use este! |
| `npm run agent:poller` | Só o poller de placas |
| `npm run token:ecrv` | Só o token refresh |
| `npm run token:updater` | Token updater antigo (Puppeteer) |
| `npm run token:refresh` | Token refresh antigo (Playwright) |

## Requisitos

- **Windows** (PowerShell para automação de cliques)
- **Node.js** instalado
- **Chrome** rodando (para token refresh)
- **e-System** instalado
- Pastas corretas: `Desktop\teste\point-recorder` e `Desktop\teste\click-automation`

## Troubleshooting

### Erro: "Click-automation não encontrado"
```
Certifique-se que a pasta existe em:
Desktop\teste\click-automation
```

### Erro: "Node.js não encontrado"
```
Instale o Node.js em: https://nodejs.org/
```

### Erro: "e-System não encontrado"
```
Ajuste o caminho em ESYSTEM_EXE_PATH no .bat
```

### Token não está sendo atualizado
```
Verifique o log em: %TEMP%\token-refresh.log
```

## Parar o script

Pressione **Ctrl+C** para encerrar todos os processos.
