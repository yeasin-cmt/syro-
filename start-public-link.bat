@echo off
title SYRO - public link
cd /d "%~dp0"

echo Starting MySQL (XAMPP)...
tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if errorlevel 1 (
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 4 /nobreak >nul
)

echo Starting the SYRO server...
start "SYRO server" /MIN "C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
timeout /t 3 /nobreak >nul

echo.
echo ============================================================
echo  Your PUBLIC link appears below, in a line like:
echo     https://something-random.trycloudflare.com
echo  Share that link. It works while this window stays open.
echo  Close this window (or press Ctrl+C) to take the site offline.
echo ============================================================
echo.
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --no-autoupdate --url http://127.0.0.1:8000
