@echo off
setlocal EnableExtensions

REM Ajuste aqui se a pasta do projeto estiver em outro lugar
set "TOKEN_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
set "LOCK_DIR=%USERPROFILE%\Desktop\teste\agent-shared.lock"
set "WAIT_SECONDS=5"
set "EXIT_CODE=0"
if "%AGENT_TOKEN_UPDATER_COMMAND%"=="" set "AGENT_TOKEN_UPDATER_COMMAND=npm run token:refresh"
if "%TOKEN_REFRESH_BROWSER_CHANNEL%"=="" set "TOKEN_REFRESH_BROWSER_CHANNEL=chrome"
if /I "%TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH%"=="%USERPROFILE%\Desktop\Firefox.exe" (
  echo [WARN] TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH aponta para launcher da Area de Trabalho. Ignorando.
  set "TOKEN_REFRESH_BROWSER_EXECUTABLE_PATH="
)

if /i not "%SKIP_SHARED_LOCK%"=="1" (
  call :acquire_lock
)

if not exist "%TOKEN_DIR%\package.json" (
  echo [ERRO] Nao encontrei package.json em:
  echo %TOKEN_DIR%
  set "EXIT_CODE=1"
  goto finish
)

cd /d "%TOKEN_DIR%"

echo [INFO] Pasta atual:
cd
echo [INFO] Navegador:
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
echo [INFO] Comando:
echo [INFO]   AGENT_TOKEN_UPDATER_COMMAND=%AGENT_TOKEN_UPDATER_COMMAND%

echo [INFO] Iniciando atualizacao de token...
call %AGENT_TOKEN_UPDATER_COMMAND%
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
  echo [ERRO] O comando %AGENT_TOKEN_UPDATER_COMMAND% encerrou com codigo %EXIT_CODE%.
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
