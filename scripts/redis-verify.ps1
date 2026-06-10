Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Write-Host "=== Redis Verification ==="

$envFile = Join-Path (Get-Location) '.env'
if (Test-Path $envFile) {
    Write-Host "Current .env Redis settings:"
    Select-String -Path $envFile -Pattern '^(CACHE_DRIVER|SESSION_DRIVER|REDIS_CLIENT|REDIS_HOST|REDIS_PORT|REDIS_DB|REDIS_CACHE_DB)=' | ForEach-Object { $_.Line }
} else {
    Write-Host ".env file not found in current folder."
}

Write-Host ""
Write-Host "Testing Redis container connection via docker-compose..."
try {
    docker compose exec redis redis-cli ping
} catch {
    Write-Host "Failed to run redis-cli through Docker. Pastikan docker compose dan service redis berjalan."
}

Write-Host ""
Write-Host "Jika Anda pakai Redis lokal, jalankan perintah ini juga:"
Write-Host "redis-cli -h 127.0.0.1 -p 6379 ping"

Write-Host ""
Write-Host "Untuk menguji Laravel Redis store, jalankan ini:"
Write-Host "php artisan tinker --execute \"Cache::store('redis')->put('test','ok',10); Cache::store('redis')->get('test');\""
