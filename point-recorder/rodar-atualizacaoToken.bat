@echo off
setlocal EnableExtensions

REM Ajuste aqui se a pasta do projeto estiver em outro lugar
set "CLICK_AUTOMATION_DIR=%USERPROFILE%\Desktop\teste\appll\click-automation"
set "LEGACY_CLICK_DIR=%USERPROFILE%\Desktop\teste\click-automation"
set "LOCK_DIR=%USERPROFILE%\Desktop\teste\agent-shared.lock"
set "WAIT_SECONDS=5"
set "EXIT_CODE=0"

REM Tentar encontrar o click-automation
if not exist "%CLICK_AUTOMATION_DIR%\package.json" (
  if exist "%LEGACY_CLICK_DIR%\package.json" (
    set "CLICK_AUTOMATION_DIR=%LEGACY_CLICK_DIR%"
  )
)

if /i not "%SKIP_SHARED_LOCK%"=="1" (
  call :acquire_lock
)

if not exist "%CLICK_AUTOMATION_DIR%\package.json" (
  echo [ERRO] Nao encontrei package.json do click-automation em:
  echo [ERRO]   %CLICK_AUTOMATION_DIR%
  echo [ERRO]   %LEGACY_CLICK_DIR%
  set "EXIT_CODE=1"
  goto finish
)

cd /d "%CLICK_AUTOMATION_DIR%"

echo [INFO] ============================================================
echo [INFO] ATUALIZACAO DE TOKEN (E-CRV SP)
echo [INFO] ============================================================
echo [INFO] Pasta atual:
cd
echo [INFO] Pasta do click-automation: %CLICK_AUTOMATION_DIR%
echo [INFO] ============================================================

echo [INFO] Instalando dependencias...
call npm install
if errorlevel 1 (
  echo [WARN] Falha no npm install. Tentando executar anyway...
)

echo [INFO] Executando automacao do E-CRV (login + captura JSESSIONID^)...
call npm run ecrv
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
  echo [ERRO] A automacao do E-CRV encerrou com codigo %EXIT_CODE%.
) else (
  echo [INFO] Automacao do E-CRV concluida com sucesso!
  echo [INFO] JSESSIONID foi capturado e salvo no banco de dados.
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
