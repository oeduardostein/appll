@echo off
setlocal EnableExtensions

REM ============================================================
REM PASSO 1: INSTALAR DEPENDENCIAS
REM ============================================================

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

set "POINT_RECORDER_DIR=%SCRIPT_DIR%"
set "CLICK_AUTOMATION_DIR=%USERPROFILE%\Desktop\teste\click-automation"

REM Verificar se point-recorder existe
if not exist "%POINT_RECORDER_DIR%\package.json" (
    set "POINT_RECORDER_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
)

cls
echo.
echo ======================================================================
echo    PASSO 1: INSTALAR DEPENDENCIAS
echo ======================================================================
echo.
echo Point-Recorder: %POINT_RECORDER_DIR%
echo Click-Automation: %CLICK_AUTOMATION_DIR%
echo.
echo ======================================================================
echo.

REM Verificar Node.js
where node >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado!
    echo [INFO] Instale em: https://nodejs.org/
    pause
    exit /b 1
)

for /f "tokens=*" %%i in ('node --version') do set NODE_VERSION=%%i
echo [INFO] Node.js versao: %NODE_VERSION%
echo.

REM ============================================================
REM 1. POINT-RECORDER
REM ============================================================

echo ======================================================================
echo [1/2] Instalando dependencias do POINT-RECORDER...
echo ======================================================================
echo.

cd /d "%POINT_RECORDER_DIR%"
if not exist "package.json" (
    echo [ERRO] package.json nao encontrado em: %CD%
    pause
    exit /b 1
)

echo [INFO] Pasta: %CD%
echo [INFO] Executando: npm install
echo.

call npm install

if errorlevel 1 (
    echo.
    echo [ERRO] npm install do point-recorder falhou!
    pause
    exit /b 1
)

echo.
echo [OK] Point-recorder - dependencias instaladas!
echo.

REM ============================================================
REM 2. CLICK-AUTOMATION
REM ============================================================

echo ======================================================================
echo [2/2] Instalando dependencias do CLICK-AUTOMATION...
echo ======================================================================
echo.

if not exist "%CLICK_AUTOMATION_DIR%\package.json" (
    echo [WARN] Click-automation nao encontrado em: %CLICK_AUTOMATION_DIR%
    echo [WARN] Pulando instalacao do click-automation...
    goto :fim
)

cd /d "%CLICK_AUTOMATION_DIR%"
echo [INFO] Pasta: %CD%
echo [INFO] Executando: npm install
echo.

call npm install

if errorlevel 1 (
    echo.
    echo [ERRO] npm install do click-automation falhou!
    echo [WARN] Mas o point-recorder esta OK. Continuando...
)

echo.
echo [OK] Click-automation - dependencias instaladas!
echo.

:fim
echo ======================================================================
echo    TODAS AS DEPENDENCIAS INSTALADAS!
echo ======================================================================
echo.
echo Pressione qualquer tecla para continuar...
pause >nul
