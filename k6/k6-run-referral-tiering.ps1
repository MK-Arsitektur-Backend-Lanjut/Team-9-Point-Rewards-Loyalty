#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Runner K6 Stress Test - Referral & Tiering System
#>

param(
    [ValidateSet('normal','race_condition','mixed')]
    [string]$Scenario = 'normal',

    [int[]]$Loads = @(10, 50, 100, 500, 1000),

    [string]$Duration = '1m',

    [string]$BaseUrl = 'http://localhost:8000',

    [switch]$SkipConfirm
)

# ================================================================
# INIT PATH (SAFE FOR ALL EXECUTION MODES)
# ================================================================
$baseDir = if ($PSScriptRoot) { $PSScriptRoot } else { Get-Location }

# ================================================================
# HEADER
# ================================================================
Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "   K6 STRESS TEST RUNNER - REFERRAL & TIERING" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# ================================================================
# CHECK DEPENDENCIES
# ================================================================
Write-Host "[INFO] Checking dependencies..." -ForegroundColor Yellow

$k6Candidates = @(
    'k6',
    'C:\Program Files\k6\k6.exe',
    "$env:LOCALAPPDATA\k6\k6.exe",
    "$env:USERPROFILE\scoop\shims\k6.exe"
)

$k6Command = $null
foreach ($candidate in $k6Candidates) {
    if (Get-Command $candidate -ErrorAction SilentlyContinue) {
        $k6Command = $candidate
        break
    }
}

if (-not $k6Command) {
    Write-Host "[ERROR] k6 not found. Install: https://k6.io/docs/get-started/installation/" -ForegroundColor Red
    exit 1
}

$k6Version = & $k6Command version 2>&1
Write-Host "[OK] k6 found: $k6Version" -ForegroundColor Green

# ================================================================
# CHECK SCRIPT
# ================================================================
$scriptPath = Join-Path $baseDir "referral-tiering-stress.js"

if (-not (Test-Path $scriptPath)) {
    Write-Host "[ERROR] Script not found: $scriptPath" -ForegroundColor Red
    exit 1
}

Write-Host "[OK] Script: $scriptPath" -ForegroundColor Green

# ================================================================
# CHECK API CONNECTION
# ================================================================
Write-Host ""
Write-Host "[INFO] Checking connection to $BaseUrl ..." -ForegroundColor Yellow

try {
    $response = Invoke-WebRequest -Uri "$BaseUrl/api/membership/tiers" `
        -Method GET -TimeoutSec 5 -ErrorAction Stop

    Write-Host "[OK] API reachable (HTTP $($response.StatusCode))" -ForegroundColor Green
}
catch {
    Write-Host "[WARN] API not responding at $BaseUrl" -ForegroundColor Yellow
    Write-Host "       Make sure service is running (docker compose up -d)" -ForegroundColor Gray

    if (-not $SkipConfirm) {
        $ans = Read-Host "Continue anyway? (y/N)"
        if ($ans -notmatch '^[yY]') { exit 1 }
    }
}

# ================================================================
# CREATE RESULTS DIRECTORY
# ================================================================
$resultsDir = Join-Path $baseDir "results"

if (-not (Test-Path $resultsDir)) {
    New-Item -ItemType Directory -Path $resultsDir | Out-Null
    Write-Host "[OK] Results directory created: $resultsDir" -ForegroundColor Green
}

# ================================================================
# CONFIG SUMMARY
# ================================================================
Write-Host ""
Write-Host "[CONFIG]" -ForegroundColor Cyan
Write-Host "Scenario   : $Scenario"
Write-Host "Loads      : $($Loads -join ', ') VUs"
Write-Host "Duration   : $Duration per test"
Write-Host "Base URL   : $BaseUrl"
Write-Host "Output Dir : $resultsDir"

$totalMinutes = ($Loads.Count) * ([int]($Duration -replace 'm','') + 1)

Write-Host ""
Write-Host "Estimated total runtime: ~$totalMinutes minutes" -ForegroundColor Gray
Write-Host ""

if (-not $SkipConfirm) {
    $ans = Read-Host "Start stress test? (y/N)"
    if ($ans -notmatch '^[yY]') {
        Write-Host "[CANCELLED]" -ForegroundColor Red
        exit 0
    }
}

# ================================================================
# RUN TESTS
# ================================================================
$results = @()
$startAll = Get-Date

foreach ($load in $Loads) {

    Write-Host ""
    Write-Host ("-" * 60) -ForegroundColor DarkCyan
    Write-Host "[RUN] Load: $load VUs | Scenario: $Scenario | Duration: $Duration" -ForegroundColor Cyan
    Write-Host ("-" * 60) -ForegroundColor DarkCyan

    $timestamp  = Get-Date -Format "yyyyMMdd_HHmmss"
    $outputFile = Join-Path $resultsDir "${Scenario}_load${load}_${timestamp}.json"

    $rampUpVal = if ($load -ge 1000) { '60s' }
        elseif ($load -ge 500) { '30s' }
        else { '15s' }

    $startTest = Get-Date

    & $k6Command run `
        --env BASE_URL=$BaseUrl `
        --env SCENARIO=$Scenario `
        --env LOAD=$load `
        --env DURATION=$Duration `
        --env RAMP_UP=$rampUpVal `
        --env RAMP_DOWN='10s' `
        --env REFERRER_ID=1 `
        --summary-export="$outputFile" `
        "$scriptPath"

    $exitCode = $LASTEXITCODE
    $elapsed  = [Math]::Round(((Get-Date) - $startTest).TotalSeconds, 0)

    $status = if ($exitCode -eq 0) { 'PASSED' } else { 'FAILED' }
    $statusColor = if ($status -eq 'PASSED') { 'Green' } else { 'Yellow' }

    Write-Host ""
    Write-Host ("[$status] Load=$load | Time=${elapsed}s") -ForegroundColor $statusColor

    $results += [PSCustomObject]@{
        Load       = $load
        Scenario   = $Scenario
        Status     = $status
        ElapsedSec = $elapsed
        OutputFile = $outputFile
    }

    if ($load -ne $Loads[-1]) {
        $cooldown = if ($load -ge 500) { 20 } else { 10 }
        Write-Host "[INFO] Cooldown ${cooldown}s..." -ForegroundColor Gray
        Start-Sleep -Seconds $cooldown
    }
}

# ================================================================
# FINAL SUMMARY
# ================================================================
$totalElapsed = [Math]::Round(((Get-Date) - $startAll).TotalMinutes, 1)

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "                    TEST SUMMARY" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

foreach ($r in $results) {
    $color = if ($r.Status -eq 'PASSED') { 'Green' } else { 'Yellow' }
    $file  = Split-Path $r.OutputFile -Leaf

    Write-Host ("{0,5} VUs | {1,-7} | {2,5}s | {3}" -f $r.Load, $r.Status, $r.ElapsedSec, $file) `
        -ForegroundColor $color
}

Write-Host ""
Write-Host "Total runtime : $totalElapsed minutes"
Write-Host "Results dir   : $resultsDir"
Write-Host ""
Write-Host "[NEXT] Analyze results:"
Write-Host "       .\k6\k6-analyze-referral-tiering.ps1"
Write-Host ""