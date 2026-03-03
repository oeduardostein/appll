@echo off
setlocal EnableExtensions

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "DEFAULT_PROJECT_DIR=%USERPROFILE%\Desktop\teste\appll\point-recorder"
set "LEGACY_PROJECT_DIR=%USERPROFILE%\Desktop\teste\point-recorder"
set "PROJECT_DIR="
set "POLLER_BAT="
set "EXIT_CODE=0"

if not "%~1"=="" (
  set "PROJECT_DIR=%~1"
)

if not defined PROJECT_DIR if exist "%SCRIPT_DIR%\package.json" (
  set "PROJECT_DIR=%SCRIPT_DIR%"
)

if not defined PROJECT_DIR if exist "%SCRIPT_DIR%\point-recorder\package.json" (
  set "PROJECT_DIR=%SCRIPT_DIR%\point-recorder"
)

if not defined PROJECT_DIR if exist "%SCRIPT_DIR%\appll\point-recorder\package.json" (
  set "PROJECT_DIR=%SCRIPT_DIR%\appll\point-recorder"
)

if not defined PROJECT_DIR if exist "%DEFAULT_PROJECT_DIR%\package.json" (
  set "PROJECT_DIR=%DEFAULT_PROJECT_DIR%"
)

if not defined PROJECT_DIR (
  set "PROJECT_DIR=%LEGACY_PROJECT_DIR%"
)

if not exist "%PROJECT_DIR%\run-agent-poller.bat" if exist "%PROJECT_DIR%\point-recorder\run-agent-poller.bat" (
  set "PROJECT_DIR=%PROJECT_DIR%\point-recorder"
)

if exist "%SCRIPT_DIR%\run-agent-poller.bat" (
  set "POLLER_BAT=%SCRIPT_DIR%\run-agent-poller.bat"
)

if not defined POLLER_BAT if exist "%PROJECT_DIR%\run-agent-poller.bat" (
  set "POLLER_BAT=%PROJECT_DIR%\run-agent-poller.bat"
)

if not defined POLLER_BAT if exist "%SCRIPT_DIR%\point-recorder\run-agent-poller.bat" (
  set "POLLER_BAT=%SCRIPT_DIR%\point-recorder\run-agent-poller.bat"
)

if not defined POLLER_BAT if exist "%SCRIPT_DIR%\appll\point-recorder\run-agent-poller.bat" (
  set "POLLER_BAT=%SCRIPT_DIR%\appll\point-recorder\run-agent-poller.bat"
)

if not defined POLLER_BAT if exist "%DEFAULT_PROJECT_DIR%\run-agent-poller.bat" (
  set "POLLER_BAT=%DEFAULT_PROJECT_DIR%\run-agent-poller.bat"
)

if not defined POLLER_BAT if exist "%LEGACY_PROJECT_DIR%\run-agent-poller.bat" (
  set "POLLER_BAT=%LEGACY_PROJECT_DIR%\run-agent-poller.bat"
)

echo [INFO] Iniciando fluxo unificado...
echo [INFO] Script atual: %~f0
echo [INFO] Diretorio do projeto: %PROJECT_DIR%

if not defined POLLER_BAT (
  echo [ERRO] Nao encontrei run-agent-poller.bat.
  echo [ERRO] Caminhos verificados:
  echo [ERRO]   %SCRIPT_DIR%\run-agent-poller.bat
  echo [ERRO]   %SCRIPT_DIR%\point-recorder\run-agent-poller.bat
  echo [ERRO]   %SCRIPT_DIR%\appll\point-recorder\run-agent-poller.bat
  echo [ERRO]   %PROJECT_DIR%\run-agent-poller.bat
  echo [ERRO]   %DEFAULT_PROJECT_DIR%\run-agent-poller.bat
  echo [ERRO]   %LEGACY_PROJECT_DIR%\run-agent-poller.bat
  echo [ERRO] Dica: passe o caminho do projeto como argumento:
  echo [ERRO]   rodar-fluxo-agente.bat "C:\Users\llgru_rj1md3b\Desktop\teste\appll\point-recorder"
  set "EXIT_CODE=1"
  goto finish
)

echo [INFO] Chamando: %POLLER_BAT%
call "%POLLER_BAT%" "%PROJECT_DIR%"
set "EXIT_CODE=%ERRORLEVEL%"

:finish
if not "%EXIT_CODE%"=="0" (
  echo [ERRO] Fluxo finalizado com erro. Codigo %EXIT_CODE%.
) else (
  echo [INFO] Fluxo finalizado com sucesso. Codigo 0.
)
pause
exit /b %EXIT_CODE%
