@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "DEFAULT_PROJECT_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
set "PROJECT_DIR=%DEFAULT_PROJECT_DIR%"
set "POLLER_BAT="
set "EXIT_CODE=0"

if not "%~1"=="" (
  set "PROJECT_DIR=%~1"
)

if exist "%SCRIPT_DIR%run-agent-poller.bat" (
  set "POLLER_BAT=%SCRIPT_DIR%run-agent-poller.bat"
)

if not defined POLLER_BAT if exist "%SCRIPT_DIR%point-recorder\run-agent-poller.bat" (
  set "POLLER_BAT=%SCRIPT_DIR%point-recorder\run-agent-poller.bat"
)

if not defined POLLER_BAT if exist "%PROJECT_DIR%\run-agent-poller.bat" (
  set "POLLER_BAT=%PROJECT_DIR%\run-agent-poller.bat"
)

echo [INFO] Iniciando fluxo unificado...
echo [INFO] Script atual: %~f0
echo [INFO] Diretorio do projeto: %PROJECT_DIR%

if not defined POLLER_BAT (
  echo [ERRO] Nao encontrei run-agent-poller.bat.
  echo [ERRO] Caminhos verificados:
  echo [ERRO]   %SCRIPT_DIR%run-agent-poller.bat
  echo [ERRO]   %SCRIPT_DIR%point-recorder\run-agent-poller.bat
  echo [ERRO]   %PROJECT_DIR%\run-agent-poller.bat
  echo [ERRO] Dica: passe o caminho do projeto como argumento:
  echo [ERRO]   rodar-fluxo-agente.bat "C:\Users\llgru_rj1md3b\Desktop\teste\point-recorder"
  set "EXIT_CODE=1"
  goto finish
)

echo [INFO] Chamando: %POLLER_BAT%
call "%POLLER_BAT%"
set "EXIT_CODE=%ERRORLEVEL%"

:finish
if not "%EXIT_CODE%"=="0" (
  echo [ERRO] Fluxo finalizado com erro. Codigo %EXIT_CODE%.
) else (
  echo [INFO] Fluxo finalizado com sucesso. Codigo 0.
)
pause
exit /b %EXIT_CODE%
