@echo off
setlocal EnableExtensions

REM Script para executar o E-CRV com Token Refresh automatico
REM
REM Uso:
REM   run-ecrv-refresh.bat                    [usa CPF/PIN padrao]
REM   run-ecrv-refresh.bat --cpf 123...       [CPF customizado]
REM   run-ecrv-refresh.bat --interval 10      [Refresh a cada 10 min]

REM Encontrar o diretorio do script
set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

cd /d "%SCRIPT_DIR%"

echo ======================================================================
echo    E-CRV SP - TOKEN REFRESH AUTOMATICO
echo ======================================================================
echo.
echo Pasta: %CD%
echo.
echo Este script ira:
echo   1. Fazer login no E-CRV
echo   2. Salvar o JSESSIONID no banco
echo   3. Manter o token ativo com refresh periodico
echo.
echo Pressione Ctrl+C para encerrar a qualquer momento.
echo.
echo ======================================================================
echo.

REM Verificar se Node.js esta instalado
where node >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado!
    pause
    exit /b 1
)

REM Verificar se package.json existe
if not exist "package.json" (
    echo [ERRO] package.json nao encontrado em: %CD%
    echo [ERRO] Execute este script dentro da pasta click-automation
    pause
    exit /b 1
)

REM Verificar se o script .js existe
if not exist "run-ecrv-with-refresh.js" (
    echo [ERRO] run-ecrv-with-refresh.js nao encontrado em: %CD%
    pause
    exit /b 1
)

REM Verificar se node_modules existe
if not exist "node_modules\" (
    echo [INFO] Instalando dependencias...
    echo [INFO] Pasta: %CD%
    call npm install
    if errorlevel 1 (
        echo [ERRO] Falha ao instalar dependencias.
        pause
        exit /b 1
    )
)

echo [INFO] Iniciando E-CRV com Token Refresh...
echo.

REM Executar com caminho completo para evitar problemas
node "%SCRIPT_DIR%\run-ecrv-with-refresh.js" %*

set "EXIT_CODE=%ERRORLEVEL%"

echo.
echo ======================================================================
if "%EXIT_CODE%"=="0" (
    echo    ENCERRADO
) else (
    echo    ENCERRADO COM ERRO - Codigo: %EXIT_CODE%
)
echo ======================================================================
pause
exit /b %EXIT_CODE%
