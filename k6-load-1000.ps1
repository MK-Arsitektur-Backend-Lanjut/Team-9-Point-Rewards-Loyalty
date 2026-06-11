#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Run K6 Stress Test dengan 1000 concurrent users
    
.DESCRIPTION
    Preset untuk menjalankan stress test dengan 1000 users
    Durasi: 4 menit (20s ramp up, 2.5m test, 50s ramp down)
    
.WARNING
    Test ini akan menggunakan resources yang cukup besar!
    Pastikan sistem sudah siap sebelum menjalankan.
#>

Write-Host "🚀 Running K6 Stress Test: 1000 Concurrent Users" -ForegroundColor Yellow
Write-Host ""
Write-Host "⚠️  WARNING: Ini akan menggunakan resources yang cukup besar!" -ForegroundColor Red
Write-Host ""

$confirm = Read-Host "Apakah Anda yakin ingin melanjutkan? (y/n)"
if ($confirm -ne 'y' -and $confirm -ne 'Y') {
    Write-Host "❌ Test dibatalkan" -ForegroundColor Red
    exit 1
}

& ".\k6-run-stress-test.ps1" `
    -Load 1000 `
    -Duration 2m `
    -Scenario normal

exit $LASTEXITCODE
