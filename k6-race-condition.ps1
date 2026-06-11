#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Run K6 Stress Test dengan Race Condition Scenario
    
.DESCRIPTION
    Script untuk menjalankan stress test khusus untuk mendeteksi race condition
    pada endpoint redeem reward. 
    
    Scenario ini akan:
    - Menambah poin besar ke user
    - Melakukan 10 concurrent redeem requests dari user yang sama
    - Mengecek apakah balance akhir konsisten
    
    Gunakan untuk konfigurasi berbeda:
    - 100 users: .\k6-race-condition.ps1 -Load 100
    - 500 users: .\k6-race-condition.ps1 -Load 500
    - 1000 users: .\k6-race-condition.ps1 -Load 1000
#>

param(
    [Parameter(Mandatory=$false)]
    [int]$Load = 100
)

Write-Host "🚀 Running K6 Race Condition Test ($Load concurrent users)" -ForegroundColor Cyan
Write-Host ""

& ".\k6-run-stress-test.ps1" `
    -Load $Load `
    -Duration 2m `
    -Scenario race_condition

exit $LASTEXITCODE
