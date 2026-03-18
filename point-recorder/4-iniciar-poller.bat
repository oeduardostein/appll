@echo off
setlocal EnableExtensions

REM ============================================================
REM PASSO 4: INICIAR APENAS AGENT POLLER
REM
REM Fluxo novo:
REM   - O refresh do E-CRV fica por conta da extensao.
REM   - Este script executa somente o agent:poller.
REM   - O poller NAO deve fazer login no e-System.
REM   - O poller deve apenas focar a janela do e-System e consultar.
REM
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
echo    PASSO 4: APENAS AGENT POLLER
echo ======================================================================
echo.
echo Point-Recorder: %POINT_RECORDER_DIR%
echo.
echo Este script ira executar:
echo.
echo   1. AGENT POLLER (primeiro plano)
echo      - Aguarda consultas de placas
echo      - Foca a janela do e-System
echo      - Processa as solicitacoes
echo.
echo [IMPORTANTE] Refresh do E-CRV desativado neste passo.
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
REM OVERRIDES DO POLLER (apenas foco + consulta)
REM ============================================================

echo ======================================================================
echo [1/2] Aplicando configuracao de execucao...
echo ======================================================================
echo.

REM Desabilita qualquer atualizacao automatica do E-CRV no worker.
set "AGENT_TOKEN_UPDATER_ENABLED=false"
set "AGENT_TOKEN_UPDATER_COMMAND="

REM Garante que o poller nao rode login do e-System (nem no start, nem por template de login separado).
set "AGENT_LOGIN_BOOTSTRAP_ON_START=false"
set "AGENT_LOGIN_TEMPLATE_PATH="
set "AGENT_LOGIN_PASSWORD="

REM Mantem o preflight apenas para foco de janela.
set "AGENT_PREFLIGHT_ENABLED=true"
if "%AGENT_PREFLIGHT_FOCUS_EXE_PATH%"=="" set "AGENT_PREFLIGHT_FOCUS_EXE_PATH=C:\SH Sistemas\System Desp SX\eSystemDesp.exe"
set "AGENT_PREFLIGHT_OCR_ENABLED=false"
set "AGENT_PREFLIGHT_FAIL_IF_NOT_MATCHED=false"
set "AGENT_APP_KILL_AFTER_SCREENSHOT=false"

echo [OK] Overrides aplicados:
echo [INFO] AGENT_TOKEN_UPDATER_ENABLED=%AGENT_TOKEN_UPDATER_ENABLED%
echo [INFO] AGENT_LOGIN_BOOTSTRAP_ON_START=%AGENT_LOGIN_BOOTSTRAP_ON_START%
echo [INFO] AGENT_LOGIN_TEMPLATE_PATH=(vazio)
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
echo   [DESATIVADO] Token refresh do E-CRV via script
echo   [STARTING]  Agent Poller: primeiro plano
echo.
echo O Agent Poller ira focar o e-System e processar consultas.
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
