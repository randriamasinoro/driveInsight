@echo off
title Serveur Location de Voitures
cd /d "%~dp0"

echo ============================================
echo   Serveur Location de Voitures - Park DTTS
echo ============================================
echo.

REM Chercher PHP (XAMPP ou systeme)
set PHP_EXE=
if exist "C:\xampp\php\php.exe"   set PHP_EXE=C:\xampp\php\php.exe
if exist "C:\php\php.exe"         set PHP_EXE=C:\php\php.exe
if "%PHP_EXE%"=="" where php >nul 2>&1 && set PHP_EXE=php

if "%PHP_EXE%"=="" (
    echo ERREUR : PHP introuvable.
    echo Installez XAMPP sur C:\xampp ou ajoutez PHP au PATH.
    pause
    exit /b 1
)

REM Creer la base de donnees si elle n'existe pas
if not exist "location_voiture.sqlite" (
    echo Initialisation de la base de donnees...
    "%PHP_EXE%" init_db.php > nul
    echo Base de donnees creee.
    echo.
)

echo Serveur demarre sur : http://localhost:8080
echo Appuyez sur Ctrl+C pour arreter.
echo.
start "" "http://localhost:8080"
"%PHP_EXE%" -S localhost:8080
pause
