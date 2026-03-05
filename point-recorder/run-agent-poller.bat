@echo off
setlocal EnableExtensions

REM Ajuste aqui se a pasta do projeto estiver em outro lugar
set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "DEFAULT_PROJECT_DIR=%USERPROFILE%\Desktop\teste\appll\point-recorder"
set "LEGACY_PROJECT_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
set "PROJECT_DIR="
if not "%~1"=="" set "PROJECT_DIR=%~1"
if not defined PROJECT_DIR if exist "%SCRIPT_DIR%\package.json" set "PROJECT_DIR=%SCRIPT_DIR%"
if not defined PROJECT_DIR if exist "%SCRIPT_DIR%\point-recorder\package.json" set "PROJECT_DIR=%SCRIPT_DIR%\point-recorder"
if not defined PROJECT_DIR if exist "%DEFAULT_PROJECT_DIR%\package.json" set "PROJECT_DIR=%DEFAULT_PROJECT_DIR%"
if not defined PROJECT_DIR set "PROJECT_DIR=%LEGACY_PROJECT_DIR%"
if not exist "%PROJECT_DIR%\package.json" if exist "%PROJECT_DIR%\point-recorder\package.json" set "PROJECT_DIR=%PROJECT_DIR%\point-recorder"

if "%AGENT_TOKEN_UPDATER_ENABLED%"=="" set "AGENT_TOKEN_UPDATER_ENABLED=1"
if "%AGENT_TOKEN_UPDATER_DIR%"=="" set "AGENT_TOKEN_UPDATER_DIR=."
if "%AGENT_TOKEN_UPDATER_COMMAND%"=="" set "AGENT_TOKEN_UPDATER_COMMAND=npm run token:refresh"
if "%AGENT_TOKEN_UPDATER_IDLE_GRACE_MS%"=="" set "AGENT_TOKEN_UPDATER_IDLE_GRACE_MS=2500"
if "%AGENT_TOKEN_UPDATER_STOP_TIMEOUT_MS%"=="" set "AGENT_TOKEN_UPDATER_STOP_TIMEOUT_MS=15000"
if "%TOKEN_REFRESH_BROWSER_CHANNEL%"=="" set "TOKEN_REFRESH_BROWSER_CHANNEL=chrome"
if /I "%TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH%"=="%USERPROFILE%\Desktop\Firefox.exe" (
  echo [WARN] TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH aponta para launcher da Area de Trabalho. Ignorando.
  set "TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH="
)
if "%AGENT_PREFLIGHT_ENABLED%"=="" set "AGENT_PREFLIGHT_ENABLED=1"
if "%AGENT_PREFLIGHT_FOCUS_EXE_PATH%"=="" set "AGENT_PREFLIGHT_FOCUS_EXE_PATH=C:\SH Sistemas\System Desp SX\eSystemDesp.exe"
if "%AGENT_PREFLIGHT_FOCUS_WAIT_MS%"=="" set "AGENT_PREFLIGHT_FOCUS_WAIT_MS=350"
if "%AGENT_PREFLIGHT_REQUIRE_FOCUS%"=="" set "AGENT_PREFLIGHT_REQUIRE_FOCUS=1"
if "%AGENT_PREFLIGHT_OCR_ENABLED%"=="" set "AGENT_PREFLIGHT_OCR_ENABLED=0"
if "%AGENT_PREFLIGHT_EXPECTED_KEYWORDS%"=="" set "AGENT_PREFLIGHT_EXPECTED_KEYWORDS=e-system desp,utilitarios"
if "%AGENT_PREFLIGHT_MIN_KEYWORD_MATCHES%"=="" set "AGENT_PREFLIGHT_MIN_KEYWORD_MATCHES=1"
if "%AGENT_PREFLIGHT_FAIL_IF_NOT_MATCHED%"=="" set "AGENT_PREFLIGHT_FAIL_IF_NOT_MATCHED=0"
set "LOCK_DIR=%USERPROFILE%\Desktop\teste\agent-shared.lock"
set "WAIT_SECONDS=5"
set "EXIT_CODE=0"

call :pre_cleanup
if errorlevel 1 (
  set "EXIT_CODE=1"
  goto finish
)

if /i not "%SKIP_SHARED_LOCK%"=="1" (
  call :acquire_lock
)

if not exist "%PROJECT_DIR%\package.json" (
  echo [ERRO] Nao encontrei package.json em:
  echo %PROJECT_DIR%
  set "EXIT_CODE=1"
  goto finish
)

cd /d "%PROJECT_DIR%"

echo [INFO] Pasta atual:
cd
echo [INFO] Token updater integrado:
echo [INFO]   AGENT_TOKEN_UPDATER_ENABLED=%AGENT_TOKEN_UPDATER_ENABLED%
echo [INFO]   AGENT_TOKEN_UPDATER_DIR=%AGENT_TOKEN_UPDATER_DIR%
echo [INFO]   AGENT_TOKEN_UPDATER_COMMAND=%AGENT_TOKEN_UPDATER_COMMAND%
echo [INFO] Navegador do token updater:
echo [INFO]   TOKEN_REFRESH_BROWSER_CHANNEL=%TOKEN_REFRESH_BROWSER_CHANNEL%
if defined TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH (
  echo [INFO]   TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH=%TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH%
) else (
  echo [INFO]   TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH=(nao definido, usando channel^)
)
if defined TOKEN_REFRESH_USER_DATA_DIR (
  echo [INFO]   TOKEN_REFRESH_USER_DATA_DIR=%TOKEN_REFRESH_USER_DATA_DIR%
) else (
  echo [INFO]   TOKEN_REFRESH_USER_DATA_DIR=(padrao local do point-recorder^)
)
echo [INFO] Preflight do e-System:
echo [INFO]   AGENT_PREFLIGHT_ENABLED=%AGENT_PREFLIGHT_ENABLED%
echo [INFO]   AGENT_PREFLIGHT_FOCUS_EXE_PATH=%AGENT_PREFLIGHT_FOCUS_EXE_PATH%
echo [INFO]   AGENT_PREFLIGHT_OCR_ENABLED=%AGENT_PREFLIGHT_OCR_ENABLED%

echo [INFO] Instalando dependencias...
call npm install
if errorlevel 1 (
  echo [ERRO] Falha no npm install.
  set "EXIT_CODE=1"
  goto finish
)

echo [INFO] Iniciando agent poller...
call npm run agent:poller
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
  echo [ERRO] O comando npm run agent:poller encerrou com codigo %EXIT_CODE%.
)

goto finish

:pre_cleanup
echo [INFO] Encerrando processos node.exe/npm.exe antes do poller...
taskkill /F /IM node.exe /IM npm.exe >nul 2>&1
echo [INFO] Limpando lock compartilhado anterior (se existir)...
if exist "%LOCK_DIR%" (
  rmdir /S /Q "%LOCK_DIR%" >nul 2>&1
)
exit /b 0

:acquire_lock
:acquire_try
2>nul md "%LOCK_DIR%" && (
  > "%LOCK_DIR%\owner.txt" echo %~nx0 ^| %DATE% %TIME%
  echo [INFO] Lock compartilhado adquirido por %~nx0.
  exit /b 0
)
echo [INFO] Outro processo ja estah executando. Aguardando %WAIT_SECONDS%s para tentar novamente...
timeout /t %WAIT_SECONDS% /nobreak >nul
goto acquire_try

:release_lock
if exist "%LOCK_DIR%\owner.txt" del /f /q "%LOCK_DIR%\owner.txt" >nul 2>&1
2>nul rd "%LOCK_DIR%"
echo [INFO] Lock compartilhado liberado.
exit /b 0

:finish
if /i not "%SKIP_SHARED_LOCK%"=="1" (
  call :release_lock
)
if /i not "%NO_PAUSE%"=="1" pause
exit /b %EXIT_CODE%
