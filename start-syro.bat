@echo off
title SYRO - local server
cd /d "%~dp0"

echo Starting MySQL (XAMPP)...
tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if errorlevel 1 (
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 4 /nobreak >nul
)

echo Opening http://127.0.0.1:8000 ...
start "" "http://127.0.0.1:8000"

echo SYRO is running. Keep this window open. Press Ctrl+C to stop.
"C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
