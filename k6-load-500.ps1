#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Run K6 Stress Test dengan 500 concurrent users
    
.DESCRIPTION
    Preset untuk menjalankan stress test dengan 500 users
    Durasi: 3 menit (15s ramp up, 2m test, 45s ramp down)
#>

Write-Host "🚀 Running K6 Stress Test: 500 Concurrent Users" -ForegroundColor Cyan
Write-Host ""

& ".\k6-run-stress-test.ps1" `
    -Load 500 `
    -Duration 2m `
    -Scenario normal

exit $LASTEXITCODE
