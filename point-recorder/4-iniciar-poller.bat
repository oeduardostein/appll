@echo off
setlocal EnableExtensions

REM ============================================================
REM PASSO 4: INICIAR AGENT POLLER + KEEP-ALIVE E-CRV
REM   - Mantem JSESSIONID ativo no E-CRV e no banco
REM   - Usa a janela do Chrome ja aberta (sem abrir outra)
REM   - Poller NAO faz login automatico no e-System
REM   - Poller apenas foca e-System e processa consultas
REM ============================================================

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

set "POINT_RECORDER_DIR=%SCRIPT_DIR%"
if not exist "%POINT_RECORDER_DIR%\package.json" (
    set "POINT_RECORDER_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
)

cls
echo.
echo ======================================================================
echo    PASSO 4: AGENT POLLER + KEEP-ALIVE E-CRV
echo ======================================================================
echo.
echo Point-Recorder: %POINT_RECORDER_DIR%
echo.
echo Este script ira executar:
echo.
echo   1. TOKEN UPDATER E-CRV (em paralelo - modo always)
echo      - Mantem sessao ativa
echo      - Atualiza JSESSIONID no banco
echo.
echo   2. AGENT POLLER (primeiro plano)
echo      - Aguarda consultas de placas
echo      - Foca a janela do e-System
echo      - Processa as solicitacoes
echo.
echo [IMPORTANTE] Login automatico do e-System desativado neste passo.
echo.
echo Pressione Ctrl+C para encerrar tudo.
echo.
echo ======================================================================
echo.

REM Verificar Node.js
where node >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado!
    exit /b 1
)

REM Verificar point-recorder
if not exist "%POINT_RECORDER_DIR%\package.json" (
    echo [ERRO] Point-recorder nao encontrado: %POINT_RECORDER_DIR%
    exit /b 1
)

cd /d "%POINT_RECORDER_DIR%"

REM ============================================================
REM OVERRIDES DO POLLER (foco + consulta + keep-alive E-CRV)
REM ============================================================

echo ======================================================================
echo [1/2] Aplicando configuracao de execucao...
echo ======================================================================
echo.

REM Keep-alive do E-CRV junto do poller.
set "AGENT_TOKEN_UPDATER_ENABLED=true"
set "AGENT_TOKEN_UPDATER_COMMAND=npm run token:updater"
set "AGENT_TOKEN_UPDATER_MODE=always"
set "AGENT_TOKEN_UPDATER_START_ON_BOOT=true"
if "%AGENT_TOKEN_UPDATER_IDLE_GRACE_MS%"=="" set "AGENT_TOKEN_UPDATER_IDLE_GRACE_MS=1000"

REM Credenciais/intervalos do updater E-CRV (pode sobrescrever por variavel de ambiente).
if "%ECRV_CPF%"=="" set "ECRV_CPF=44922011811"
if "%ECRV_PIN%"=="" set "ECRV_PIN=1234"
if "%TOKEN_UPDATER_USE_EXISTING_CHROME_ONLY%"=="" set "TOKEN_UPDATER_USE_EXISTING_CHROME_ONLY=true"
if "%TOKEN_UPDATER_AUTO_START_CHROME%"=="" set "TOKEN_UPDATER_AUTO_START_CHROME=false"
if "%TOKEN_UPDATER_AUTO_LOGIN_WHEN_NEEDED%"=="" set "TOKEN_UPDATER_AUTO_LOGIN_WHEN_NEEDED=false"
if "%TOKEN_UPDATER_CDP_URL%"=="" set "TOKEN_UPDATER_CDP_URL=http://127.0.0.1:9222"
if "%TOKEN_UPDATER_REQUIRE_ECRV_TAB%"=="" set "TOKEN_UPDATER_REQUIRE_ECRV_TAB=true"
if "%TOKEN_UPDATER_SESSION_REFRESH_INTERVAL_MS%"=="" set "TOKEN_UPDATER_SESSION_REFRESH_INTERVAL_MS=300000"
if "%TOKEN_UPDATER_SESSION_CHECK_INTERVAL_MS%"=="" set "TOKEN_UPDATER_SESSION_CHECK_INTERVAL_MS=30000"
if "%TOKEN_UPDATER_MAX_SESSION_AGE_MS%"=="" set "TOKEN_UPDATER_MAX_SESSION_AGE_MS=3300000"

REM Garante que o poller nao rode login do e-System (nem no start, nem por template de login separado).
set "AGENT_LOGIN_BOOTSTRAP_ON_START=false"
set "AGENT_LOGIN_TEMPLATE_PATH="
set "AGENT_DISABLE_LOGIN_TEMPLATE=true"
set "AGENT_LOGIN_PASSWORD="

REM Mantem o preflight apenas para foco de janela.
set "AGENT_PREFLIGHT_ENABLED=true"
if "%AGENT_PREFLIGHT_FOCUS_EXE_PATH%"=="" set "AGENT_PREFLIGHT_FOCUS_EXE_PATH=C:\SH Sistemas\System Desp SX\eSystemDesp.exe"
set "AGENT_PREFLIGHT_OCR_ENABLED=false"
set "AGENT_PREFLIGHT_FAIL_IF_NOT_MATCHED=false"
set "AGENT_APP_KILL_AFTER_SCREENSHOT=false"

echo [OK] Overrides aplicados:
echo [INFO] AGENT_TOKEN_UPDATER_ENABLED=%AGENT_TOKEN_UPDATER_ENABLED%
echo [INFO] AGENT_TOKEN_UPDATER_COMMAND=%AGENT_TOKEN_UPDATER_COMMAND%
echo [INFO] AGENT_TOKEN_UPDATER_MODE=%AGENT_TOKEN_UPDATER_MODE%
echo [INFO] AGENT_TOKEN_UPDATER_START_ON_BOOT=%AGENT_TOKEN_UPDATER_START_ON_BOOT%
echo [INFO] ECRV_CPF=%ECRV_CPF%
echo [INFO] TOKEN_UPDATER_USE_EXISTING_CHROME_ONLY=%TOKEN_UPDATER_USE_EXISTING_CHROME_ONLY%
echo [INFO] TOKEN_UPDATER_AUTO_START_CHROME=%TOKEN_UPDATER_AUTO_START_CHROME%
echo [INFO] TOKEN_UPDATER_AUTO_LOGIN_WHEN_NEEDED=%TOKEN_UPDATER_AUTO_LOGIN_WHEN_NEEDED%
echo [INFO] TOKEN_UPDATER_CDP_URL=%TOKEN_UPDATER_CDP_URL%
echo [INFO] TOKEN_UPDATER_REQUIRE_ECRV_TAB=%TOKEN_UPDATER_REQUIRE_ECRV_TAB%
echo [INFO] TOKEN_UPDATER_SESSION_REFRESH_INTERVAL_MS=%TOKEN_UPDATER_SESSION_REFRESH_INTERVAL_MS%
echo [INFO] TOKEN_UPDATER_SESSION_CHECK_INTERVAL_MS=%TOKEN_UPDATER_SESSION_CHECK_INTERVAL_MS%
echo [INFO] TOKEN_UPDATER_MAX_SESSION_AGE_MS=%TOKEN_UPDATER_MAX_SESSION_AGE_MS%
echo [INFO] AGENT_LOGIN_BOOTSTRAP_ON_START=%AGENT_LOGIN_BOOTSTRAP_ON_START%
echo [INFO] AGENT_LOGIN_TEMPLATE_PATH=(vazio)
echo [INFO] AGENT_DISABLE_LOGIN_TEMPLATE=%AGENT_DISABLE_LOGIN_TEMPLATE%
echo [INFO] AGENT_PREFLIGHT_ENABLED=%AGENT_PREFLIGHT_ENABLED%
echo [INFO] AGENT_PREFLIGHT_OCR_ENABLED=%AGENT_PREFLIGHT_OCR_ENABLED%
echo [INFO] AGENT_PREFLIGHT_FOCUS_EXE_PATH=%AGENT_PREFLIGHT_FOCUS_EXE_PATH%
echo.

REM ============================================================
REM 2. INICIAR AGENT POLLER (primeiro plano)
REM ============================================================

echo ======================================================================
echo [2/2] Iniciando AGENT POLLER...
echo ======================================================================
echo.
echo   [STARTING]  Token updater E-CRV em paralelo
echo   [STARTING]  Agent Poller: primeiro plano
echo.
echo O Agent Poller ira focar o e-System e processar consultas.
echo O token updater mantera o E-CRV ativo e o JSESSIONID atualizado.
echo Pressione Ctrl+C para encerrar tudo.
echo.
echo ======================================================================
echo.

call npm run agent:poller
set "POLLER_EXIT_CODE=%ERRORLEVEL%"

REM ============================================================
REM 3. LIMPEZA
REM ============================================================

echo.
echo ======================================================================
echo [INFO] Encerrando script...
echo ======================================================================
echo.

if "%POLLER_EXIT_CODE%"=="0" (
    echo ======================================================================
    echo    ENCERRADO NORMALMENTE
    echo ======================================================================
) else (
    echo ======================================================================
    echo    ENCERRADO COM ERRO - Codigo: %POLLER_EXIT_CODE%
    echo ======================================================================
)

echo.
exit /b %POLLER_EXIT_CODE%
