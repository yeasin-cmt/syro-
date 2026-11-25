<#
    Builds a ready-to-upload package of SYRO for InfinityFree (or any shared host without SSH).

    Output: deploy\infinityfree\
        htdocs\                 the complete site, exactly as it must sit in InfinityFree's htdocs folder
        upload-zips\            the same content split into zips under 9 MB (InfinityFree's file limit is 10 MB)
        syro-database.sql       database export to import with phpMyAdmin

    Usage (from the project folder):  powershell -ExecutionPolicy Bypass -File scripts\build-infinityfree.ps1
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$php = 'C:\xampp\php\php.exe'
$mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe'
$deploy = Join-Path $root 'deploy\infinityfree'
$out = Join-Path $deploy 'htdocs'

Write-Host '1/7 Building front-end assets...' -ForegroundColor Cyan
Push-Location $root
npm run build | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }
Pop-Location

Write-Host '2/7 Copying application files...' -ForegroundColor Cyan
if (Test-Path $deploy) { Remove-Item $deploy -Recurse -Force }
New-Item -ItemType Directory -Force $out | Out-Null
$excludeDirs = @('.git', '.claude', '.vscode', 'node_modules', 'vendor', 'tests', 'deploy', 'scripts', 'public\storage', 'public\hot') |
    ForEach-Object { Join-Path $root $_ }
$excludeFiles = @('.env', 'index.html', '*.bat', 'phpunit.xml', '.phpunit.result.cache', '*.log', 'hot')
robocopy $root $out /E /NFL /NDL /NJH /NJS /NP /XD $excludeDirs /XF $excludeFiles | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy failed with code $LASTEXITCODE" }

# Clean runtime folders but keep their structure (Laravel needs them to exist and be writable).
foreach ($d in 'storage\framework\sessions', 'storage\framework\views', 'storage\framework\cache\data', 'storage\logs', 'storage\app\public') {
    $p = Join-Path $out $d
    if (Test-Path $p) { Get-ChildItem $p -Force | Where-Object Name -ne '.gitignore' | Remove-Item -Recurse -Force }
    New-Item -ItemType Directory -Force $p | Out-Null
}
Get-ChildItem (Join-Path $out 'bootstrap\cache') -Filter *.php | Remove-Item -Force

Write-Host '3/7 Installing production PHP packages (no dev tools)...' -ForegroundColor Cyan
Push-Location $out
$env:COMPOSER_PROCESS_TIMEOUT = '120'
# Composer and artisan print normal progress on stderr; Windows PowerShell 5.1 would treat that as a
# fatal error under 'Stop', so relax it for these native calls and check the exit codes instead.
$ErrorActionPreference = 'Continue'
# --no-scripts: package discovery is run below with an explicit PHP call.
cmd /c "composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts 2>&1"
$composerExit = $LASTEXITCODE
cmd /c "`"$php`" artisan package:discover 2>&1"
$discoverExit = $LASTEXITCODE
$ErrorActionPreference = 'Stop'
if ($composerExit -ne 0) { throw "composer install failed ($composerExit)" }
if ($discoverExit -ne 0) { throw "package:discover failed ($discoverExit)" }
Pop-Location

Write-Host '4/7 Copying product images into the web folder...' -ForegroundColor Cyan
$imgSrc = Join-Path $root 'storage\app\public'
$imgDst = Join-Path $out 'public\storage'
New-Item -ItemType Directory -Force $imgDst | Out-Null
robocopy $imgSrc $imgDst /E /NFL /NDL /NJH /NJS /NP /XF .gitignore | Out-Null

Write-Host '5/7 Writing production .env and .htaccess...' -ForegroundColor Cyan
$appKey = 'base64:' + [Convert]::ToBase64String((1..32 | ForEach-Object { [byte](Get-Random -Maximum 256) }))
@"
APP_NAME=SYRO
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=https://YOUR-SITE.infinityfreeapp.com
APP_TIMEZONE=Asia/Dhaka
APP_CURRENCY=BDT
APP_CURRENCY_SYMBOL=৳
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=single
LOG_LEVEL=error

# ---- Fill these 4 lines from InfinityFree > Control Panel > MySQL Databases ----
DB_CONNECTION=mysql
DB_HOST=sqlXXX.infinityfree.com
DB_PORT=3306
DB_DATABASE=if0_XXXXXXXX_syro
DB_USERNAME=if0_XXXXXXXX
DB_PASSWORD=YOUR_VPANEL_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=database

# InfinityFree has no symlinks: uploaded images go straight into public/storage
PUBLIC_DISK_ROOT=public/storage
PUBLIC_DISK_URL=/storage

# Free hosting blocks outgoing email; order emails are written to the log instead.
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@syro.local"
MAIL_FROM_NAME="SYRO"
"@ | Set-Content -Path (Join-Path $out '.env') -Encoding UTF8

@'
# Serve the Laravel app from /public while keeping clean URLs.
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Never expose secrets or source files.
    RewriteRule ^(\.env|\.git|composer\.(json|lock)|package(-lock)?\.json|artisan|vite\.config\.js)($|/) - [F,L]
    RewriteRule ^(app|bootstrap|config|database|resources|routes|storage|vendor)(/|$) - [F,L]

    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

<FilesMatch "^\.">
    Require all denied
</FilesMatch>
'@ | Set-Content -Path (Join-Path $out '.htaccess') -Encoding ASCII

# PHP's .env reader must not see a UTF-8 BOM.
foreach ($f in '.env') {
    $p = Join-Path $out $f
    $text = [IO.File]::ReadAllText($p)
    [IO.File]::WriteAllText($p, $text.TrimStart([char]0xFEFF), (New-Object Text.UTF8Encoding $false))
}

Write-Host '6/7 Exporting the database...' -ForegroundColor Cyan
$sql = Join-Path $deploy 'syro-database.sql'
$skipData = 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'
$ignore = $skipData | ForEach-Object { "--ignore-table=syro.$_" }
& $mysqldump -u root --default-character-set=utf8mb4 --single-transaction --skip-comments --skip-add-locks $ignore syro | Out-File $sql -Encoding utf8
& $mysqldump -u root --default-character-set=utf8mb4 --no-data --skip-comments $skipData syro | Out-File $sql -Encoding utf8 -Append
$sqlText = [IO.File]::ReadAllText($sql).TrimStart([char]0xFEFF)
[IO.File]::WriteAllText($sql, $sqlText, (New-Object Text.UTF8Encoding $false))

Write-Host '7/7 Creating upload zips (each under 9 MB)...' -ForegroundColor Cyan
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
$zipDir = Join-Path $deploy 'upload-zips'
New-Item -ItemType Directory -Force $zipDir | Out-Null
$limit = 8.5MB
$files = Get-ChildItem $out -Recurse -File -Force | Sort-Object FullName
$part = 0; $zip = $null; $fs = $null
foreach ($file in $files) {
    if ($null -eq $zip -or $fs.Length -ge $limit) {
        if ($zip) { $zip.Dispose(); $fs.Dispose() }
        $part++
        $fs = [IO.File]::Open((Join-Path $zipDir ("syro-part{0:D2}.zip" -f $part)), 'Create')
        $zip = New-Object IO.Compression.ZipArchive($fs, [IO.Compression.ZipArchiveMode]::Create, $true)
    }
    # Forward slashes so the paths extract correctly on Linux servers.
    $entryName = $file.FullName.Substring($out.Length + 1).Replace('\', '/')
    [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entryName, 'Optimal') | Out-Null
    $fs.Flush()
}
if ($zip) { $zip.Dispose(); $fs.Dispose() }

# Report against InfinityFree's limits.
$inodes = (Get-ChildItem $out -Recurse -Force | Measure-Object).Count
$largest = Get-ChildItem $out -Recurse -File -Force | Sort-Object Length -Descending | Select-Object -First 1
$bigPhp = Get-ChildItem $out -Recurse -File -Filter *.php | Where-Object Length -gt 1MB
Write-Host ''
Write-Host 'Done.' -ForegroundColor Green
Write-Host ("  Files + folders (inodes): {0}  (InfinityFree limit ~30,000)" -f $inodes)
Write-Host ("  Largest file: {0} ({1:N1} MB, limit 10 MB)" -f $largest.Name, ($largest.Length / 1MB))
Write-Host ("  PHP files over 1 MB: {0}" -f $bigPhp.Count)
Write-Host ("  Upload zips: {0} in {1}" -f $part, $zipDir)
Get-ChildItem $zipDir | ForEach-Object { Write-Host ("    {0}  {1:N1} MB" -f $_.Name, ($_.Length / 1MB)) }
Write-Host ("  Database: {0} ({1:N0} KB)" -f $sql, ((Get-Item $sql).Length / 1KB))
