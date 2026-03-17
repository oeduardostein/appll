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

REM Configuracao do Token Updater E-CRV
if "%TOKEN_UPDATER_ENABLED%"=="" set "TOKEN_UPDATER_ENABLED=1"
if "%TOKEN_UPDATER_START_MODE%"=="" set "TOKEN_UPDATER_START_MODE=after-poller"
if "%TOKEN_UPDATER_INITIAL_DELAY_MS%"=="" set "TOKEN_UPDATER_INITIAL_DELAY_MS=10000"

REM Configuracao do click-automation (E-CRV) - login inicial
if "%CLICK_AUTOMATION_ENABLED%"=="" set "CLICK_AUTOMATION_ENABLED=0"
if "%CLICK_AUTOMATION_DIR%"=="" set "CLICK_AUTOMATION_DIR=%USERPROFILE%\Desktop\teste\appll\click-automation"

REM Preflight do e-System
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
echo [INFO] ============================================================
echo [INFO] TOKEN UPDATER E-CRV:
echo [INFO]   TOKEN_UPDATER_ENABLED=%TOKEN_UPDATER_ENABLED%
echo [INFO]   TOKEN_UPDATER_START_MODE=%TOKEN_UPDATER_START_MODE%
echo [INFO]   TOKEN_UPDATER_INITIAL_DELAY_MS=%TOKEN_UPDATER_INITIAL_DELAY_MS%
echo [INFO] ============================================================
echo [INFO] CLICK AUTOMATION (login inicial):
echo [INFO]   CLICK_AUTOMATION_ENABLED=%CLICK_AUTOMATION_ENABLED%
echo [INFO]   CLICK_AUTOMATION_DIR=%CLICK_AUTOMATION_DIR%
echo [INFO] ============================================================
echo [INFO] Preflight do e-System:
echo [INFO]   AGENT_PREFLIGHT_ENABLED=%AGENT_PREFLIGHT_ENABLED%
echo [INFO]   AGENT_PREFLIGHT_FOCUS_EXE_PATH=%AGENT_PREFLIGHT_FOCUS_EXE_PATH%
echo [INFO]   AGENT_PREFLIGHT_OCR_ENABLED=%AGENT_PREFLIGHT_OCR_ENABLED%
echo [INFO] ============================================================

echo [INFO] Instalando dependencias do point-recorder...
call npm install
if errorlevel 1 (
  echo [ERRO] Falha no npm install do point-recorder.
  set "EXIT_CODE=1"
  goto finish
)

REM Login inicial no E-CRV (opcional - se quiser fazer login antes de tudo)
if /i "%CLICK_AUTOMATION_ENABLED%"=="1" (
  echo [INFO] ============================================================
  echo [INFO] EXECUTANDO LOGIN INICIAL NO E-CRV
  echo [INFO] ============================================================

  if exist "%CLICK_AUTOMATION_DIR%\package.json" (
    echo [INFO] Instalando dependencias do click-automation...
    cd /d "%CLICK_AUTOMATION_DIR%"
    call npm install
    if errorlevel 1 (
      echo [WARN] Falha no npm install do click-automation. Continuando anyway...
    )

    echo [INFO] Executando login inicial no E-CRV...
    call npm run ecrv
    if errorlevel 1 (
      echo [WARN] O login inicial falhou, o token updater tentara novamente...
    ) else (
      echo [INFO] Login inicial concluido com sucesso!
    )
  ) else (
    echo [WARN] Click-automation nao encontrado em: %CLICK_AUTOMATION_DIR%
  )

  cd /d "%PROJECT_DIR%"
)

echo [INFO] ============================================================
echo [INFO] INICIANDO AGENT POLLER (consulta de placas^)
echo [INFO] ============================================================

REM Iniciar o Token Updater EM PARALELO (como processo separado)
set "TOKEN_UPDATER_PID="
if /i "%TOKEN_UPDATER_ENABLED%"=="1" (
  echo [INFO] ============================================================
  echo [INFO] INICIANDO TOKEN UPDATER E-CRV (paralelo^)
  echo [INFO] ============================================================

  start /MIN "" cmd /c "node \"%PROJECT_DIR%\agent\token-updater-ecrv.mjs\""

  echo [INFO] Token Updater iniciado em background
  echo [INFO] ============================================================

  REM Aguardar um pouco para o token updater inicializar
  echo [INFO] Aguardando %TOKEN_UPDATER_INITIAL_DELAY_MS%ms para o Token Updater inicializar...
  set /a "WAIT_SEC=%TOKEN_UPDATER_INITIAL_DELAY_MS% / 1000"
  timeout /t %WAIT_SEC% /nobreak >nul
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
echo [INFO] ============================================================
echo [INFO] Encerrando processos...
echo [INFO] ============================================================

REM Encerrar Token Updater se estiver rodando
taskkill /F /IM node.exe >nul 2>&1

if /i not "%SKIP_SHARED_LOCK%"=="1" (
  call :release_lock
)
if /i not "%NO_PAUSE%"=="1" pause
exit /b %EXIT_CODE%
