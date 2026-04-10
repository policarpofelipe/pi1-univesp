@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"

if not exist "composer.phar" (
    echo Baixando Composer ^(composer.phar^)...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile '%~dp0composer.phar' -UseBasicParsing"
    if errorlevel 1 (
        echo Falha no download. Baixe manualmente: https://getcomposer.org/download/
        pause
        exit /b 1
    )
)

set "PHP_EXE="
where php >nul 2>&1 && set "PHP_EXE=php"

if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "C:\laragon\bin\php\php-8.3.12-Win32-vs16-x64\php.exe" set "PHP_EXE=C:\laragon\bin\php\php-8.3.12-Win32-vs16-x64\php.exe"
if not defined PHP_EXE if exist "C:\wamp64\bin\php\php8.2.0\php.exe" set "PHP_EXE=C:\wamp64\bin\php\php8.2.0\php.exe"

if not defined PHP_EXE (
    for /d %%D in ("C:\laragon\bin\php\php-*") do if exist "%%~D\php.exe" set "PHP_EXE=%%~D\php.exe"
)

if not defined PHP_EXE (
    echo.
    echo PHP nao foi encontrado no PATH nem em caminhos comuns ^(XAMPP/Laragon/Wamp^).
    echo.
    echo Opcoes:
    echo   1^) Instale o Composer para Windows: https://getcomposer.org/Composer-Setup.exe
    echo      ^(o instalador ajuda a localizar o PHP^)
    echo   2^) Ou instale XAMPP/Laragon e edite este arquivo instalar-dependencias.bat
    echo      para apontar PHP_EXE= para o seu php.exe
    echo   3^) Ou adicione a pasta do PHP ao PATH do Windows e rode de novo.
    echo.
    pause
    exit /b 1
)

echo Usando: !PHP_EXE!
"!PHP_EXE!" "%~dp0composer.phar" install --no-interaction
if errorlevel 1 (
    echo.
    echo composer install falhou.
    pause
    exit /b 1
)

echo.
echo Concluido. Pasta vendor\ criada.
pause
exit /b 0
