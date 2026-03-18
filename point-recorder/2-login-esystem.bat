@echo off
setlocal EnableExtensions

REM ============================================================
REM PASSO 2: ABRIR E-System E FAZER LOGIN AUTOMATICO
REM ============================================================

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

set "POINT_RECORDER_DIR=%SCRIPT_DIR%"
if not exist "%POINT_RECORDER_DIR%\package.json" (
    set "POINT_RECORDER_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
)

set "ESYSTEM_EXE_PATH=C:\SH Sistemas\System Desp SX\eSystemDesp.exe"

cls
echo.
echo ======================================================================
echo    PASSO 2: ABRIR E-System E FAZER LOGIN
echo ======================================================================
echo.
echo e-System: %ESYSTEM_EXE_PATH%
echo Point-Recorder: %POINT_RECORDER_DIR%
echo.
echo Este script ira:
echo   1. Abrir o e-System
echo   2. Trazer para frente
echo   3. Executar login automatico (senha + 2 cliques)
echo.
echo ======================================================================
echo.

REM Verificar se e-System existe
if not exist "%ESYSTEM_EXE_PATH%" (
    echo [ERRO] e-System nao encontrado em: %ESYSTEM_EXE_PATH%
    echo.
    echo [INFO] Edite este arquivo e altere ESYSTEM_EXE_PATH
    echo [INFO] Ou faca login manualmente.
    echo.
    exit /b 1
)

REM Verificar Node.js
where node >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado!
    exit /b 1
)

REM Ir para pasta do point-recorder
cd /d "%POINT_RECORDER_DIR%"

REM ============================================================
REM 1. ABRIR E-SYSTEM
REM ============================================================

echo [1/2] Abrindo e-System...
start "" "%ESYSTEM_EXE_PATH%"

echo [INFO] Aguardando e-System iniciar (10 segundos)...
timeout /t 10 /nobreak >nul

echo [INFO] Trazendo e-System para frente...
powershell -Command "Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class Win32 { [DllImport(\"user32.dll\")] public static extern bool SetForegroundWindow(IntPtr hWnd); [DllImport(\"user32.dll\")] public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow); }'; $proc = Get-Process eSystemDesp -ErrorAction SilentlyContinue; if ($proc) { [Win32]::SetForegroundWindow($proc.MainWindowHandle); [Win32]::ShowWindow($proc.MainWindowHandle, 9); Write-Host 'e-System trazido para frente.'; } else { Write-Host 'e-System nao encontrado.'; }"

echo [INFO] Aguardando mais 5 segundos para o e-System ficar pronto...
timeout /t 5 /nobreak >nul

echo [OK] e-System aberto e pronto.
echo.

REM ============================================================
REM 2. EXECUTAR LOGIN AUTOMATICO
REM ============================================================

echo ======================================================================
echo [2/2] Executando login automatico...
echo ======================================================================
echo.
echo   [INFO] Digitando senha: ll
echo   [INFO] Pressionando Enter
echo   [INFO] Executando 2 cliques pos-login
echo.
echo ======================================================================
echo.

call npm run login:esystem

if errorlevel 1 (
    echo.
    echo ======================================================================
    echo [ERRO] Login automatico falhou!
    echo ======================================================================
    echo.
    echo [INFO] O e-System esta aberto, mas o login nao foi completo.
    echo [INFO] Faca o login manualmente.
    echo.
    exit /b 1
)

echo.
echo ======================================================================
echo    E-SYSTEM ABERTO E LOGIN CONCLUIDO!
echo ======================================================================
echo.
echo [OK] O e-System esta aberto e logado.
echo [OK] Os 2 cliques pos-login foram executados.
echo.
echo [INFO] Encerrando este script...
timeout /t 2 /nobreak >nul
exit /b 0
