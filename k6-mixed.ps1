#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Run K6 Stress Test dengan Mixed Operations Scenario
    
.DESCRIPTION
    Script untuk menjalankan stress test dengan mixed operations:
    - 40% Add Points
    - 30% Check Balance  
    - 20% Redeem Reward
    - 10% Redeem hanya
    
    Scenario ini lebih realistis mencerminkan traffic production.
#>

param(
    [Parameter(Mandatory=$false)]
    [int]$Load = 100
)

Write-Host "🚀 Running K6 Mixed Operations Test ($Load concurrent users)" -ForegroundColor Cyan
Write-Host ""

& ".\k6-run-stress-test.ps1" `
    -Load $Load `
    -Duration 2m `
    -Scenario mixed

exit $LASTEXITCODE
