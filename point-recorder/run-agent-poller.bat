@echo off
setlocal

REM Ajuste aqui se a pasta do projeto estiver em outro lugar
set "PROJECT_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
if "%AGENT_TOKEN_UPDATER_ENABLED%"=="" set "AGENT_TOKEN_UPDATER_ENABLED=1"
if "%AGENT_TOKEN_UPDATER_DIR%"=="" set "AGENT_TOKEN_UPDATER_DIR=..\atualizacaoToken"
if "%AGENT_TOKEN_UPDATER_IDLE_GRACE_MS%"=="" set "AGENT_TOKEN_UPDATER_IDLE_GRACE_MS=2500"
if "%AGENT_TOKEN_UPDATER_STOP_TIMEOUT_MS%"=="" set "AGENT_TOKEN_UPDATER_STOP_TIMEOUT_MS=15000"
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
