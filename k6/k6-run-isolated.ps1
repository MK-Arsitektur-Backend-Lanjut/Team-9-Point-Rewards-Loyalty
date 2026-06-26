#!/usr/bin/env pwsh

$Loads = @(10, 50)
$Endpoints = @('list_tiers', 'generate_referral', 'recalculate_tier', 'apply_referral')
$BaseUrl = 'http://localhost:8000'
$resultsDir = Join-Path $PSScriptRoot "results"

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "   K6 ISOLATED ENDPOINTS STRESS TEST (10 & 50 VUs)" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

if (-not (Test-Path $resultsDir)) {
    New-Item -ItemType Directory -Path $resultsDir | Out-Null
}

$scriptPath = Join-Path $PSScriptRoot "isolated-endpoints.js"

foreach ($endpoint in $Endpoints) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Magenta
    Write-Host " TESTING ENDPOINT: $endpoint" -ForegroundColor Magenta
    Write-Host "========================================" -ForegroundColor Magenta

    foreach ($load in $Loads) {
        Write-Host ""
        Write-Host "  -> Running $load VUs..." -ForegroundColor Cyan

        $timestamp  = Get-Date -Format "yyyyMMdd_HHmmss"
        $outputFile = Join-Path $resultsDir "isolated_${endpoint}_load${load}_${timestamp}.json"

        $k6Command = 'k6'
        
        & $k6Command run `
            --env BASE_URL=$BaseUrl `
            --env ENDPOINT=$endpoint `
            --env LOAD=$load `
            --summary-export="$outputFile" `
            "$scriptPath"

        $exitCode = $LASTEXITCODE

        if ($exitCode -eq 0) {
            Write-Host "     [PASSED] Result saved to $(Split-Path $outputFile -Leaf)" -ForegroundColor Green
        } else {
            Write-Host "     [FAILED] Threshold exceeded! Result saved to $(Split-Path $outputFile -Leaf)" -ForegroundColor Yellow
        }

        Start-Sleep -Seconds 5
    }
}

Write-Host ""
Write-Host "[DONE] Isolated testing completed." -ForegroundColor Green
