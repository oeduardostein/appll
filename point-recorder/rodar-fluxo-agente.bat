@echo off
setlocal EnableExtensions

REM ============================================================
REM FLUXO UNIFICADO - E-SYSTEM + POLLER + E-CRV + TOKEN REFRESH
REM ============================================================
REM
REM Ordem de execucao:
REM 1. Instalar dependencias
REM 2. Login no e-System
REM 3. Login no E-CRV (npm run ecrv) - UMA VEZ
REM 4. Iniciar Agent Poller (primeiro plano)
REM 5. Iniciar Token Refresh (background) - SO DEPOIS que E-CRV terminar
REM
REM ============================================================

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

REM ============================================================
REM CONFIGURACOES
REM ============================================================

set "POINT_RECORDER_DIR=%SCRIPT_DIR%"
set "CLICK_AUTOMATION_DIR=%USERPROFILE%\Desktop\teste\click-automation"

REM Verificar se point-recorder existe
if not exist "%POINT_RECORDER_DIR%\package.json" (
    set "POINT_RECORDER_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
)

REM Verificar se click-automation existe
if not exist "%CLICK_AUTOMATION_DIR%\package.json" (
    if exist "%POINT_RECORDER_DIR%\..\click-automation\package.json" (
        set "CLICK_AUTOMATION_DIR=%POINT_RECORDER_DIR%\..\click-automation"
    )
)

REM e-System
set "ESYSTEM_EXE_PATH=C:\SH Sistemas\System Desp SX\eSystemDesp.exe"
set "ESYSTEM_PREFLIGHT_ENABLED=1"

REM E-CRV
set "ECRV_CPF=44922011811"
set "ECRV_PIN=1234"

REM Token Refresh
set "TOKEN_REFRESH_ENABLED=1"
set "TOKEN_REFRESH_INTERVAL=5"

REM ============================================================
REM INICIO
REM ============================================================

cls
echo.
echo ======================================================================
echo    FLUXO UNIFICADO - E-SYSTEM + POLLER + E-CRV + TOKEN REFRESH
echo ======================================================================
echo.
echo Point-Recorder: %POINT_RECORDER_DIR%
echo Click-Automation: %CLICK_AUTOMATION_DIR%
echo.
echo Este script ira executar na ordem:
echo   1. Instalar dependencias
echo   2. Fazer login no e-System
echo   3. Fazer login no E-CRV (uma vez)
echo   4. Iniciar Agent Poller (aguarda placas)
echo   5. Iniciar Token Refresh (mantem E-CRV ativo)
echo.
echo Pressione Ctrl+C para encerrar.
echo.
echo ======================================================================
echo.

REM ============================================================
REM VERIFICACOES
REM ============================================================

if not exist "%POINT_RECORDER_DIR%\package.json" (
    echo [ERRO] Point-recorder nao encontrado em: %POINT_RECORDER_DIR%
    pause
    exit /b 1
)

where node >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado!
    pause
    exit /b 1
)

REM ============================================================
REM 1. INSTALL DEPENDENCIAS
REM ============================================================

echo ======================================================================
echo PASSO 1: Instalando dependencias...
echo ======================================================================
echo.

cd /d "%POINT_RECORDER_DIR%"
echo [INFO] Instalando dependencias do point-recorder...
call npm install
if errorlevel 1 (
    echo [ERRO] Falha no npm install do point-recorder.
    pause
    exit /b 1
)
echo [OK] Dependencias do point-recorder instaladas.
echo.

REM ============================================================
REM 2. LOGIN NO E-SYSTEM
REM ============================================================

echo ======================================================================
echo PASSO 2: Login no e-System...
echo ======================================================================
echo.

if /i "%ESYSTEM_PREFLIGHT_ENABLED%"=="1" (
    if exist "%ESYSTEM_EXE_PATH%" (
        echo [INFO] Iniciando e-System...
        start "" "%ESYSTEM_EXE_PATH%"
        echo [INFO] Aguardando e-System iniciar (10 segundos)...
        timeout /t 10 /nobreak >nul

        echo [INFO] Trazendo e-System para frente...
        powershell -Command "Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class Win32 { [DllImport(\"user32.dll\")] public static extern bool SetForegroundWindow(IntPtr hWnd); [DllImport(\"user32.dll\")] public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow); }'; $proc = Get-Process eSystemDesp -ErrorAction SilentlyContinue; if ($proc) { [Win32]::SetForegroundWindow($proc.MainWindowHandle); [Win32]::ShowWindow($proc.MainWindowHandle, 9); Write-Host 'e-System trazido para frente.'; } else { Write-Host 'e-System nao encontrado.'; }"

        echo [INFO] Aguardando mais 5 segundos...
        timeout /t 5 /nobreak >nul
    ) else (
        echo [WARN] e-System nao encontrado em: %ESYSTEM_EXE_PATH%
        echo [INFO] Por favor, faca login manualmente.
        pause
    )
)
echo.

REM ============================================================
REM 3. LOGIN NO E-CRV (UMA VEZ - ANTES DO POLLER)
REM ============================================================

echo ======================================================================
echo PASSO 3: Login no E-CRV (inicial)...
echo ======================================================================
echo.

if exist "%CLICK_AUTOMATION_DIR%\package.json" (
    cd /d "%CLICK_AUTOMATION_DIR%"
    echo [INFO] Executando: npm run ecrv -- --cpf %ECRV_CPF% --pin %ECRV_PIN%
    echo.

    call npm run ecrv -- --cpf %ECRV_CPF% --pin %ECRV_PIN%

    if errorlevel 1 (
        echo [WARN] Login no E-CRV falhou, mas o token refresh tentara novamente...
    ) else (
        echo [OK] Login no E-CRV concluido com sucesso!
    )
    echo.

    REM Voltar para point-recorder
    cd /d "%POINT_RECORDER_DIR%"
) else (
    echo [WARN] Click-automation nao encontrado, pulando login E-CRV...
    echo.
)

REM ============================================================
REM 4. INICIAR TOKEN REFRESH (background - ANTES do poller)
REM ============================================================

echo ======================================================================
echo PASSO 4: Iniciando Token Refresh (background)...
echo ======================================================================
echo.

if /i "%TOKEN_REFRESH_ENABLED%"=="1" (
    echo [INFO] O Token Refresh ira manter o E-CRV ativo.
    echo [INFO] Iniciando em background...

    start /MIN "" cmd /c "cd /d \"%POINT_RECORDER_DIR%\" && echo [%DATE% %TIME%] [TOKEN-REFRESH] Iniciando... > \"%TEMP%\token-refresh-log.txt\" 2>&1 && npm run token:ecrv -- --cpf %ECRV_CPF% --pin %ECRV_PIN% --interval %TOKEN_REFRESH_INTERVAL% >> \"%TEMP%\token-refresh-log.txt\" 2>&1"

    echo [OK] Token Refresh iniciado em background.
    echo [INFO] Log: %TEMP%\token-refresh-log.txt
    echo.

    REM Aguardar um pouco para o token refresh inicializar
    echo [INFO] Aguardando 5 segundos para o Token Refresh inicializar...
    timeout /t 5 /nobreak >nul
)

REM ============================================================
REM 5. INICIAR AGENT POLLER (primeiro plano - principal)
REM ============================================================

echo ======================================================================
echo PASSO 5: Iniciando Agent Poller (principal)...
echo ======================================================================
echo.
echo   - Agent Poller:  INICIANDO (primeiro plano)
echo   - Token Refresh: RODANDO (background)
echo.
echo O Agent Poller ira aguardar consultas de placas.
echo Pressione Ctrl+C para encerrar tudo.
echo.
echo ======================================================================
echo.

cd /d "%POINT_RECORDER_DIR%"
call npm run agent:poller
set "POLLER_EXIT_CODE=%ERRORLEVEL%"

REM ============================================================
REM 6. LIMPEZA
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
    echo    FLUXO FINALIZADO
    echo ======================================================================
) else (
    echo ======================================================================
    echo    FLUXO FINALIZADO COM ERRO - Codigo: %POLLER_EXIT_CODE%
    echo ======================================================================
)

echo.
pause
exit /b %POLLER_EXIT_CODE%
