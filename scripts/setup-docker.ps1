param(
    [switch]$Build,
    [switch]$SkipMigrate,
    [switch]$SkipOptimize
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Write-Host "=== Docker setup: MySQL + Redis + Laravel ==="

if ($Build) {
    Write-Host "Building and starting Docker services..."
    docker compose up -d --build
} else {
    Write-Host "Starting Docker services..."
    docker compose up -d
}

if (-not $SkipMigrate) {
    Write-Host "Running migrations in app container..."
    docker compose exec app php artisan migrate --force
}

if (-not $SkipOptimize) {
    Write-Host "Optimizing Laravel in app container..."
    docker compose exec app php artisan optimize
}

Write-Host "Checking Redis connection..."
docker compose exec redis redis-cli ping

Write-Host "Docker setup complete."
