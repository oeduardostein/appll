@echo off
setlocal EnableExtensions

REM ============================================================
REM PASSO 4: INICIAR POLLER + TOKEN REFRESH
REM ============================================================
REM
REM Este script executa:
REM   1. Token Refresh (background) - mantem E-CRV ativo
REM   2. Agent Poller (primeiro plano) - consulta placas
REM
REM ============================================================

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

set "POINT_RECORDER_DIR=%SCRIPT_DIR%"
set "CLICK_AUTOMATION_DIR=%USERPROFILE%\Desktop\teste\click-automation"

if not exist "%POINT_RECORDER_DIR%\package.json" (
    set "POINT_RECORDER_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
)

if not exist "%CLICK_AUTOMATION_DIR%\package.json" (
    if exist "%POINT_RECORDER_DIR%\..\click-automation\package.json" (
        set "CLICK_AUTOMATION_DIR=%POINT_RECORDER_DIR%\..\click-automation"
    )
)

REM Configuracoes E-CRV
set "ECRV_CPF=44922011811"
set "ECRV_PIN=1234"
set "TOKEN_REFRESH_INTERVAL=5"

cls
echo.
echo ======================================================================
echo    PASSO 4: POLLER + TOKEN REFRESH
echo ======================================================================
echo.
echo Point-Recorder: %POINT_RECORDER_DIR%
echo Click-Automation: %CLICK_AUTOMATION_DIR%
echo.
echo Este script ira executar:
echo.
echo   1. TOKEN REFRESH (background)
echo      - Mantem o E-CRV ativo
echo      - Atualiza o JSESSIONID no banco
echo      - Recarrega a cada %TOKEN_REFRESH_INTERVAL% minutos
echo.
echo   2. AGENT POLLER (primeiro plano)
echo      - Aguarda consultas de placas
echo      - Processa as solicitacoes
echo.
echo Pressione Ctrl+C para encerrar tudo.
echo.
echo ======================================================================
echo.

REM Verificar Node.js
where node >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado!
    pause
    exit /b 1
)

REM Verificar point-recorder
if not exist "%POINT_RECORDER_DIR%\package.json" (
    echo [ERRO] Point-recorder nao encontrado: %POINT_RECORDER_DIR%
    pause
    exit /b 1
)

cd /d "%POINT_RECORDER_DIR%"

REM ============================================================
REM 1. INICIAR TOKEN REFRESH (background)
REM ============================================================

echo ======================================================================
echo [1/2] Iniciando TOKEN REFRESH (background)...
echo ======================================================================
echo.

echo [INFO] Iniciando Token Refresh em background...
echo [INFO] CPF: %ECRV_CPF%
echo [INFO] Intervalo: %TOKEN_REFRESH_INTERVAL% minutos
echo [INFO] Log: %TEMP%\token-refresh-log.txt
echo.

start /MIN "" cmd /c "cd /d \"%POINT_RECORDER_DIR%\" && echo [%DATE% %TIME%] [TOKEN-REFRESH] Iniciando... > \"%TEMP%\token-refresh-log.txt\" 2>&1 && npm run token:ecrv -- --cpf %ECRV_CPF% --pin %ECRV_PIN% --interval %TOKEN_REFRESH_INTERVAL% >> \"%TEMP%\token-refresh-log.txt\" 2>&1"

echo [OK] Token Refresh iniciado em background.
echo [INFO] Aguardando 5 segundos para inicializacao...
timeout /t 5 /nobreak >nul
echo.

REM ============================================================
REM 2. INICIAR AGENT POLLER (primeiro plano)
REM ============================================================

echo ======================================================================
echo [2/2] Iniciando AGENT POLLER (primeiro plano)...
echo ======================================================================
echo.
echo   [RUNNING] Token Refresh:  background (porta 9222)
echo   [STARTING] Agent Poller:  primeiro plano
echo.
echo O Agent Poller ira aguardar consultas de placas.
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
echo [INFO] Encerrando processos...
echo ======================================================================
echo.

REM Matar processos node
taskkill /F /IM node.exe >nul 2>&1

echo [INFO] Processos encerrados.
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
pause
exit /b %POLLER_EXIT_CODE%
