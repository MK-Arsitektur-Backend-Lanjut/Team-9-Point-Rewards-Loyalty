#!/usr/bin/env pwsh
<#
.SYNOPSIS
    K6 Stress Test Runner untuk Loyalty Point Rewards System
    
.DESCRIPTION
    Script PowerShell untuk menjalankan K6 stress test dengan berbagai konfigurasi
    
.PARAMETER Load
    Jumlah concurrent users (default: 100)
    
.PARAMETER Duration
    Durasi test (default: 1m)
    
.PARAMETER Scenario
    Jenis scenario: normal, race_condition, mixed (default: normal)
    
.PARAMETER BaseUrl
    Base URL API (default: http://localhost:8000/api)
    
.EXAMPLE
    PS> .\k6-run-stress-test.ps1
    # Menjalankan dengan konfigurasi default (100 users, 1 menit)
    
    PS> .\k6-run-stress-test.ps1 -Load 500 -Duration 2m
    # Menjalankan dengan 500 concurrent users selama 2 menit
    
    PS> .\k6-run-stress-test.ps1 -Scenario race_condition -Load 1000
    # Menjalankan scenario race condition dengan 1000 users
#>

param(
    [Parameter(Mandatory=$false)]
    [int]$Load = 100,
    
    [Parameter(Mandatory=$false)]
    [string]$Duration = '1m',
    
    [Parameter(Mandatory=$false)]
    [ValidateSet('normal', 'race_condition', 'mixed')]
    [string]$Scenario = 'normal',
    
    [Parameter(Mandatory=$false)]
    [string]$BaseUrl = 'http://localhost:8000/api'
)

# Validasi K6 terinstall
Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║           K6 STRESS TEST - LOYALTY POINT REWARDS              ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$k6Check = k6 version
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ K6 tidak ditemukan atau belum terinstall!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Untuk meninstall K6, gunakan salah satu metode berikut:" -ForegroundColor Yellow
    Write-Host "  1. Chocolatey: choco install k6" -ForegroundColor White
    Write-Host "  2. NPM: npm install -g k6" -ForegroundColor White
    Write-Host "  3. Download: https://k6.io/open-source/get-started/" -ForegroundColor White
    Write-Host ""
    exit 1
}

Write-Host "✅ K6 Version: $k6Check" -ForegroundColor Green
Write-Host ""

# Setup output directory
$outputDir = "results"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$testName = "${Scenario}_load${Load}_${timestamp}"
$outputPath = Join-Path $outputDir $testName

# Buat directory jika belum ada
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

# Environment variables
$env:BASE_URL = $BaseUrl
$env:LOAD = $Load
$env:DURATION = $Duration
$env:SCENARIO = $Scenario

# Tampilkan konfigurasi
Write-Host "┌────────────────────────────────────────────────────────────────┐" -ForegroundColor Cyan
Write-Host "│                   KONFIGURASI TEST                             │" -ForegroundColor Cyan
Write-Host "├────────────────────────────────────────────────────────────────┤" -ForegroundColor Cyan
Write-Host "│ Scenario:           $Scenario" -ForegroundColor White
Write-Host "│ Concurrent Users:   $Load" -ForegroundColor White
Write-Host "│ Test Duration:      $Duration" -ForegroundColor White
Write-Host "│ Base URL:           $BaseUrl" -ForegroundColor White
Write-Host "│ Output:             $outputPath" -ForegroundColor White
Write-Host "└────────────────────────────────────────────────────────────────┘" -ForegroundColor Cyan
Write-Host ""

Write-Host "🚀 Memulai stress test..." -ForegroundColor Green
Write-Host "⏳ Silakan tunggu..." -ForegroundColor Yellow
Write-Host ""

# Jalankan K6 dengan berbagai output format
$startTime = Get-Date

k6 run k6-stress-test.js `
    --env BASE_URL="$BaseUrl" `
    --env SCENARIO="$Scenario" `
    --env LOAD="$Load" `
    --env DURATION="$Duration" `
    --summary-export="$outputPath.json" `
    --out json="$outputPath-results.ndjson" `
    2>&1 | Tee-Object -FilePath "$outputPath.log"

$exitCode = $LASTEXITCODE
$endTime = Get-Date
$duration = ($endTime - $startTime).TotalSeconds

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                      TEST COMPLETED                            ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

if ($exitCode -eq 0) {
    Write-Host "✅ TEST PASSED" -ForegroundColor Green
} else {
    Write-Host "⚠️  TEST COMPLETED WITH WARNINGS" -ForegroundColor Yellow
}

Write-Host "Duration:      $([Math]::Round($duration, 2)) detik" -ForegroundColor White
Write-Host "Log File:      $outputPath.log" -ForegroundColor White
Write-Host "Summary File:  $outputPath.json" -ForegroundColor White
Write-Host "Results File:  $outputPath-results.ndjson" -ForegroundColor White
Write-Host ""

# Tampilkan quick summary dari JSON
Write-Host "📊 QUICK SUMMARY:" -ForegroundColor Cyan
Write-Host ""

if (Test-Path "$outputPath.json") {
    $summary = Get-Content "$outputPath.json" -Raw | ConvertFrom-Json
    
    # Extract metrics
    if ($summary.metrics) {
        $summary.metrics | Get-Member -MemberType NoteProperty | ForEach-Object {
            $metricName = $_.Name
            $metricData = $summary.metrics.$metricName
            
            if ($metricData -and $metricData.values) {
                if ($metricName -like "*duration*" -or $metricName -like "*latency*") {
                    $avg = $metricData.values.avg
                    if ($null -ne $avg) {
                        $p95 = $metricData.values.'p(95)'
                        $p99 = $metricData.values.'p(99)'
                        Write-Host "  📈 $($metricName): avg=$([Math]::Round($avg, 2))ms, p95=$([Math]::Round($p95, 2))ms, p99=$([Math]::Round($p99, 2))ms" -ForegroundColor White
                    }
                } else {
                    $rate = $metricData.values.rate
                    if ($null -ne $rate) {
                        Write-Host "  ❌ $($metricName): $([Math]::Round($rate * 100, 2))%" -ForegroundColor White
                    }
                }
            }
        }
    }
}

Write-Host ""
Write-Host "💡 Tips:" -ForegroundColor Cyan
Write-Host "  • Baca file log untuk detail lengkap" -ForegroundColor Gray
Write-Host "  • Gunakan k6 cloud untuk real-time dashboards" -ForegroundColor Gray
Write-Host "  • Periksa hasil JSON untuk analisis lebih lanjut" -ForegroundColor Gray
Write-Host ""

exit $exitCode
