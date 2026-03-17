@echo off
setlocal EnableExtensions

REM ============================================================
REM INICIADOR - FLUXO COMPLETO
REM ============================================================

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

cls
echo.
echo ======================================================================
echo    FLUXO COMPLETO - SISTEMA APPLL
echo ======================================================================
echo.
echo Escolha uma opcao:
echo.
echo   [1] Iniciar tudo (Passos 1 + 2 + 3 + 4)
echo       - Instalar dependencias
echo       - Login e-System
echo       - Login E-CRV
echo       - Poller + Token Refresh
echo.
echo   [2] So inicializacao (Passos 1 + 2 + 3)
echo       - Instalar dependencias
echo       - Login e-System
echo       - Login E-CRV
echo.
echo   [3] So Poller + Token Refresh (Passo 4)
echo       - Inicia Poller e Token Refresh
echo       - Use apos ja ter feito login em tudo
echo.
echo   [0] Sair
echo.
echo ======================================================================
echo.

set /p "OPCAO=Escolha uma opcao: "

if "%OPCAO%"=="1" goto tudo
if "%OPCAO%"=="2" goto inicializacao
if "%OPCAO%"=="3" goto poller
if "%OPCAO%"=="0" goto fim
goto fim

:tudo
cls
echo.
echo ======================================================================
echo    MODO: INICIAR TUDO
echo ======================================================================
echo.

call "%SCRIPT_DIR%\1-instalar-dependencias.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\2-login-esystem.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\3-login-ecrv.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\4-iniciar-poller-e-refresh.bat"
goto fim

:inicializacao
cls
echo.
echo ======================================================================
echo    MODO: SO INICIALIZACAO
echo ======================================================================
echo.

call "%SCRIPT_DIR%\1-instalar-dependencias.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\2-login-esystem.bat"
if errorlevel 1 goto erro

call "%SCRIPT_DIR%\3-login-ecrv.bat"

cls
echo.
echo ======================================================================
echo    INICIALIZACAO CONCLUIDA!
echo ======================================================================
echo.
echo Agora execute a opcao [3] para iniciar Poller + Token Refresh.
echo.
pause
goto fim

:poller
cls
echo.
echo ======================================================================
echo    MODO: POLLER + TOKEN REFRESH
echo ======================================================================
echo.

call "%SCRIPT_DIR%\4-iniciar-poller-e-refresh.bat"
goto fim

:erro
cls
echo.
echo ======================================================================
echo    ERRO NA EXECUCAO!
echo ======================================================================
echo.
pause
exit /b 1

:fim
echo.
echo ======================================================================
echo    FIM
echo ======================================================================
echo.
pause
