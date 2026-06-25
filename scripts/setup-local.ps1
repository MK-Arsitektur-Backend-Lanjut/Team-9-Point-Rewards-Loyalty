Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Write-Host "=== Local setup: Laravel optimization + Redis verification ==="

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    Write-Host "Copied .env.example to .env"
}

function Get-EnvValue {
    param(
        [string]$Path,
        [string]$Key
    )
    $value = Get-Content $Path | Where-Object { $_ -match "^$Key=" } | Select-Object -First 1
    if ($null -eq $value) { return $null }
    return $value -replace "^$Key=", "" -replace '"', ''
}

Write-Host "Please verify .env values for DB and Redis before continuing."
Write-Host "Recommended local defaults:"
Write-Host "  DB_HOST=127.0.0.1"
Write-Host "  DB_PORT=3307"
Write-Host "  REDIS_HOST=127.0.0.1"
Write-Host "  REDIS_PORT=6379"
Write-Host "-------------------------------------------"

$cacheDriver = Get-EnvValue -Path '.env' -Key 'CACHE_DRIVER'
$sessionDriver = Get-EnvValue -Path '.env' -Key 'SESSION_DRIVER'

if (($cacheDriver -eq 'redis' -or $sessionDriver -eq 'redis') -and (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "Redis mode detected in .env. Starting Redis container..."
    docker compose up -d redis
    Write-Host "Redis container started."
}

php artisan migrate
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

Write-Host "Local optimization complete."
