@echo off
setlocal EnableDelayedExpansion

REM ============================================================
REM E-CRV SP - TOKEN REFRESH AUTOMATICO
REM ============================================================
REM
REM Execute ESTE arquivo para iniciar o E-CRV com token refresh
REM NAO execute o arquivo .js diretamente!
REM
REM ============================================================

REM Encontrar o diretorio do script, nao importa de onde foi chamado
set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

cd /d "%SCRIPT_DIR%"

cls
echo.
echo ============================================================
echo    E-CRV SP - TOKEN REFRESH AUTOMATICO
echo ============================================================
echo.
echo Pasta atual: %CD%
echo.
echo Este script ira:
echo   1. Fazer login no E-CRV
echo   2. Salvar o JSESSIONID no banco
echo   3. Manter o token ativo com refresh periodico
echo.
echo Pressione Ctrl+C para encerrar a qualquer momento.
echo.
echo ============================================================
echo.

REM Verificar se Node.js esta instalado
where node >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado!
    echo [ERRO] Por favor, instale o Node.js em: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

REM Verificar se estamos no diretorio correto (tem package.json)
if not exist "%SCRIPT_DIR%\package.json" (
    echo [ERRO] package.json nao encontrado em: %SCRIPT_DIR%
    echo [ERRO] Por favor, execute este script dentro da pasta click-automation
    echo.
    pause
    exit /b 1
)

REM Verificar se o script .js existe
if not exist "%SCRIPT_DIR%\run-ecrv-with-refresh.js" (
    echo [ERRO] run-ecrv-with-refresh.js nao encontrado em: %SCRIPT_DIR%
    echo.
    pause
    exit /b 1
)

REM Verificar se node_modules existe
if not exist "%SCRIPT_DIR%\node_modules\" (
    echo [INFO] Instalando dependencias...
    echo [INFO] Pasta: %SCRIPT_DIR%
    call npm install
    if errorlevel 1 (
        echo [ERRO] Falha ao instalar dependencias.
        pause
        exit /b 1
    )
    echo [INFO] Dependencias instaladas com sucesso!
)

echo [INFO] Iniciando E-CRV com Token Refresh...
echo [INFO] Node:
node --version
echo [INFO] Script: %SCRIPT_DIR%\run-ecrv-with-refresh.js
echo.
echo ============================================================
echo.

REM Executar com Node.js explicitamente, com caminho completo
node "%SCRIPT_DIR%\run-ecrv-with-refresh.js" %*

set "EXIT_CODE=%ERRORLEVEL%"

echo.
echo ============================================================
if "%EXIT_CODE%"=="0" (
    echo    ENCERRADO
) else (
    echo    ENCERRADO COM ERRO - Codigo: %EXIT_CODE%
)
echo ============================================================
pause
exit /b %EXIT_CODE%
