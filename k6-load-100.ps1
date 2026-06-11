#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Run K6 Stress Test dengan 100 concurrent users
    
.DESCRIPTION
    Preset untuk menjalankan stress test dengan 100 users
    Durasi: 2 menit (10s ramp up, 1m test, 50s ramp down)
#>

Write-Host "🚀 Running K6 Stress Test: 100 Concurrent Users" -ForegroundColor Cyan
Write-Host ""

& ".\k6-run-stress-test.ps1" `
    -Load 100 `
    -Duration 1m `
    -Scenario normal

exit $LASTEXITCODE
