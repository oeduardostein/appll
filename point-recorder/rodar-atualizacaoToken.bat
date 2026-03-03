@echo off
setlocal EnableExtensions

REM Ajuste aqui se a pasta do projeto estiver em outro lugar
set "TOKEN_DIR=%USERPROFILE%\Desktop\teste\atualizacaoToken"
set "LOCK_DIR=%USERPROFILE%\Desktop\teste\agent-shared.lock"
set "WAIT_SECONDS=5"
set "EXIT_CODE=0"
if "%CHROME_CHANNEL%"=="" set "CHROME_CHANNEL=chrome"

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
echo [INFO]   CHROME_CHANNEL=%CHROME_CHANNEL%
if defined CHROME_USER_DATA_DIR (
  echo [INFO]   CHROME_USER_DATA_DIR=%CHROME_USER_DATA_DIR%
) else (
  echo [INFO]   CHROME_USER_DATA_DIR=(padrao do atualizacaoToken)
)
if defined CHROME_PROFILE_NAME (
  echo [INFO]   CHROME_PROFILE_NAME=%CHROME_PROFILE_NAME%
) else (
  echo [INFO]   CHROME_PROFILE_NAME=(nao definido)
)

echo [INFO] Iniciando atualizacao de token...
call npm start
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
  echo [ERRO] O comando npm start encerrou com codigo %EXIT_CODE%.
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
