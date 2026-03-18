@echo off
setlocal EnableExtensions

REM ============================================================
REM PASSO 3: LOGIN NO E-CRV
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

set "ECRV_CPF=44922011811"
set "ECRV_PIN=1234"

cls
echo.
echo ======================================================================
echo    PASSO 3: LOGIN NO E-CRV
echo ======================================================================
echo.
echo Click-Automation: %CLICK_AUTOMATION_DIR%
echo CPF: %ECRV_CPF%
echo PIN: %ECRV_PIN%
echo.
echo ======================================================================
echo.

REM Verificar se click-automation existe
if not exist "%CLICK_AUTOMATION_DIR%\package.json" (
    echo [ERRO] Click-automation nao encontrado em: %CLICK_AUTOMATION_DIR%
    echo.
    echo [INFO] Certifique-se que a pasta existe:
    echo [INFO] Desktop\teste\click-automation
    echo.
    exit /b 1
)

cd /d "%CLICK_AUTOMATION_DIR%"

echo [INFO] Pasta: %CD%
echo [INFO] Executando: npm run ecrv -- --cpf %ECRV_CPF% --pin %ECRV_PIN%
echo.
echo [AVISO] A automacao de login do E-CRV vai iniciar...
echo [AVISO] Certifique-se que o Chrome esta fechado antes!
echo.
echo ======================================================================
echo.

call npm run ecrv -- --cpf %ECRV_CPF% --pin %ECRV_PIN%

if errorlevel 1 (
    echo.
    echo ======================================================================
    echo [ERRO] Login no E-CRV falhou!
    echo ======================================================================
    echo.
    exit /b 1
)

echo.
echo ======================================================================
echo    LOGIN NO E-CRV CONCLUIDO!
echo ======================================================================
echo.
echo [OK] O login foi realizado com sucesso.
echo [OK] O Chrome deve estar aberto no E-CRV.
echo.
echo [INFO] Encerrando este script...
timeout /t 2 /nobreak >nul
exit /b 0
