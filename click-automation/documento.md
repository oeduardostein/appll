# Extracao de Cookie no `extract-jsessionid.js`

Este documento descreve como a extracao do cookie (`JSESSIONID`/`PHPSESSID`) esta sendo feita atualmente no fluxo do E-CRV.

## Objetivo

Capturar o token de sessao do E-CRV e salvar no banco MySQL (`admin_settings.key = 'jsessionid'`).

## Arquivo principal

- `click-automation/extract-jsessionid.js`

## Ordem de execucao

1. Importa dependencias:
- `playwright` (CDP)
- `mysql2/promise` (persistencia)
- `puppeteer` (fallback com Chrome local)

2. Tenta capturar via CDP (`http://127.0.0.1:9222`):
- Faz retry (`ECRV_CDP_RETRIES`, default `12`)
- Intervalo de retry (`ECRV_CDP_RETRY_DELAY_MS`, default `2000ms`)

3. Se conectar no CDP:
- Varre todos os `contexts` e cookies
- Procura `JSESSIONID` e `PHPSESSID`
- Se nao achar nos cookies, tenta `document.cookie` nas paginas

4. Se CDP falhar (ou nao achar token):
- Entra no fallback por snapshot de perfil do Chrome

## Fallback por snapshot de perfil

### 1) Descoberta de perfil

Origem do `User Data`:
- `CHROME_USER_DATA_DIR` (se definido)
- senao: `%LOCALAPPDATA%\\Google\\Chrome\\User Data`

Perfis candidatos:
- `CHROME_PROFILE_DIRECTORY` (se definido)
- senao: `Default` + `Profile N`

### 2) Snapshot temporario

Cria pasta temporaria em `%TEMP%` e copia:
- `Local State`
- `<Perfil>\\Network\\Cookies`

### 3) Leitura no snapshot

Abre Chrome instalado localmente com `puppeteer.launch`:
- `executablePath`: detectado automaticamente (ou `CHROME_EXECUTABLE_PATH`)
- `userDataDir`: snapshot temporario
- `--profile-directory=<perfil>`

Depois le:
- `page.cookies(TARGET_URL)`
- fallback para `page.cookies()`
- se preciso, navega no alvo e tenta de novo

### 4) Arquivo bloqueado (`EBUSY/EPERM`)

Se detectar lock no arquivo `Cookies`:
- aplica metodo de retry com fechamento de Chrome
- tenta `taskkill /IM chrome.exe /T`
- se falhar, tenta `taskkill /F /IM chrome.exe /T`
- refaz snapshot e nova tentativa de leitura

Controle desse comportamento:
- `ECRV_FORCE_CLOSE_CHROME_ON_LOCK=1` (default)
- `ECRV_FORCE_CLOSE_CHROME_ON_LOCK=0` desativa fechamento forçado

### 5) Reabertura do Chrome

Se o fallback fechou Chrome, ao final tenta reabrir em:
- `https://e-crvsp.sp.gov.br/`

## Persistencia no banco

Quando encontra token:

1. Conecta no MySQL (`dbConfig` no proprio script)
2. Consulta `admin_settings` por `key = 'jsessionid'`
3. Faz `UPDATE` se existe, senao `INSERT`

## Variaveis de ambiente aceitas

- `ECRV_CDP_URL` (default: `http://127.0.0.1:9222`)
- `ECRV_CDP_RETRIES` (default: `12`)
- `ECRV_CDP_RETRY_DELAY_MS` (default: `2000`)
- `ECRV_FORCE_CLOSE_CHROME_ON_LOCK` (default: `1`)
- `CHROME_USER_DATA_DIR` (override do User Data do Chrome)
- `CHROME_PROFILE_DIRECTORY` (forcar perfil especifico)
- `CHROME_EXECUTABLE_PATH` (forcar caminho do `chrome.exe`)

## Resumo tecnico

A extracao atual usa duas camadas:

1. **Principal**: CDP no Chrome ja aberto (mais rapido e direto).
2. **Fallback**: snapshot local do perfil + leitura via Chrome local com Puppeteer (para quando CDP nao sobe ou arquivo de cookies esta bloqueado).
