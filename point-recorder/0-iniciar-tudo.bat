@echo off
setlocal EnableExtensions

REM ============================================================
REM INICIADOR AUTOMATICO - FLUXO COMPLETO
REM ============================================================

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

cls
echo.
echo ======================================================================
echo    FLUXO AUTOMATICO - SISTEMA APPLL
echo ======================================================================
echo.
echo Iniciando automaticamente:
echo   1. Instalar dependencias
echo   2. Login e-System
echo   3. Login E-CRV
echo   4. Iniciar Agent Poller
echo.
echo ======================================================================
echo.

call "%SCRIPT_DIR%\1-instalar-dependencias.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\2-login-esystem.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\3-login-ecrv.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\4-iniciar-poller.bat"
set "FINAL_EXIT_CODE=%ERRORLEVEL%"
goto fim

:erro
cls
echo.
echo ======================================================================
echo    ERRO NA EXECUCAO!
echo ======================================================================
echo.
exit /b 1

:fim
echo.
echo ======================================================================
echo    FLUXO FINALIZADO
echo ======================================================================
echo.
exit /b %FINAL_EXIT_CODE%
