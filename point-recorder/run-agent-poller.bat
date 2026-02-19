@echo off
setlocal

REM Ajuste aqui se a pasta do projeto estiver em outro lugar
set "PROJECT_DIR=%USERPROFILE%\Desktop\teste\point-recorder"

if not exist "%PROJECT_DIR%\package.json" (
  echo [ERRO] Nao encontrei package.json em:
  echo %PROJECT_DIR%
  pause
  exit /b 1
)

cd /d "%PROJECT_DIR%"

echo [INFO] Pasta atual:
cd

echo [INFO] Instalando dependencias...
call npm install
if errorlevel 1 (
  echo [ERRO] Falha no npm install.
  pause
  exit /b 1
)

echo [INFO] Iniciando agent poller...
call npm run agent:poller
set EXIT_CODE=%ERRORLEVEL%

if not "%EXIT_CODE%"=="0" (
  echo [ERRO] O comando npm run agent:poller encerrou com codigo %EXIT_CODE%.
)

pause
exit /b %EXIT_CODE%
