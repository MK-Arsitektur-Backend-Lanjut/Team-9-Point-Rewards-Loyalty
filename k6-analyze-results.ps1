#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Analyze K6 Test Results
    
.DESCRIPTION
    Script untuk menganalisis hasil K6 stress test dan generate report
    
.PARAMETER ResultFile
    Path ke JSON summary file dari K6 test result
    
.EXAMPLE
    PS> .\k6-analyze-results.ps1
    # Auto-detect latest result file
    
    PS> .\k6-analyze-results.ps1 -ResultFile "results/normal_load100_*.json"
    # Analyze specific result file
#>

param(
    [Parameter(Mandatory=$false)]
    [string]$ResultFile
)

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║           K6 RESULTS ANALYZER - GENERATE REPORT               ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Auto-detect latest result file
if (-not $ResultFile) {
    $resultFiles = Get-ChildItem "results/*.json" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending
    
    if ($resultFiles.Count -eq 0) {
        Write-Host "❌ Tidak ada result file ditemukan di folder 'results/'" -ForegroundColor Red
        Write-Host ""
        Write-Host "Silakan jalankan stress test terlebih dahulu:" -ForegroundColor Yellow
        Write-Host "  .\k6-load-100.ps1" -ForegroundColor White
        Write-Host ""
        exit 1
    }
    
    $ResultFile = $resultFiles[0].FullName
    Write-Host "📁 Menggunakan file: $($resultFiles[0].Name)" -ForegroundColor Green
    Write-Host "   Last modified: $($resultFiles[0].LastWriteTime)" -ForegroundColor Gray
    Write-Host ""
}

# Load JSON
if (-not (Test-Path $ResultFile)) {
    Write-Host "❌ File tidak ditemukan: $ResultFile" -ForegroundColor Red
    exit 1
}

$json = Get-Content $ResultFile -Raw | ConvertFrom-Json
$metrics = $json.metrics

Write-Host "📊 ANALYSIS RESULTS" -ForegroundColor Cyan
Write-Host "─" * 64 -ForegroundColor Cyan
Write-Host ""

# ================================================================
# 1. RESPONSE TIME ANALYSIS
# ================================================================
Write-Host "1️⃣  RESPONSE TIME ANALYSIS" -ForegroundColor Yellow
Write-Host "─" * 64 -ForegroundColor Gray

$responseTimeMetrics = @(
    @{name="add_points_duration"; label="Add Points"; target=500},
    @{name="redeem_duration"; label="Redeem Reward"; target=500},
    @{name="balance_duration"; label="Check Balance"; target=200},
    @{name="http_req_duration"; label="Overall HTTP Requests"; target=1000}
)

$responseTimeMetrics | ForEach-Object {
    $metricName = $_.name
    $label = $_.label
    $target = $_.target
    
    if ($metrics.$metricName -and $metrics.$metricName.values) {
        $data = $metrics.$metricName.values
        $avg = [Math]::Round($data.avg, 2)
        $p95 = [Math]::Round($data.'p(95)', 2)
        $p99 = [Math]::Round($data.'p(99)', 2)
        $min = [Math]::Round($data.min, 2)
        $max = [Math]::Round($data.max, 2)
        
        # Status
        $status = if ($p95 -lt $target) { "✅ GOOD" } elseif ($p95 -lt ($target * 1.5)) { "⚠️ ACCEPTABLE" } else { "❌ SLOW" }
        
        Write-Host ""
        Write-Host "  📈 $label (Target: <${target}ms)" -ForegroundColor White
        Write-Host "     Average:  $avg ms" -ForegroundColor Gray
        Write-Host "     p(95):    $p95 ms  $status" -ForegroundColor White
        Write-Host "     p(99):    $p99 ms" -ForegroundColor Gray
        Write-Host "     Range:    $min - $max ms" -ForegroundColor Gray
    }
}

# ================================================================
# 2. ERROR RATE ANALYSIS
# ================================================================
Write-Host ""
Write-Host ""
Write-Host "2️⃣  ERROR RATE ANALYSIS" -ForegroundColor Yellow
Write-Host "─" * 64 -ForegroundColor Gray

$errorMetrics = @(
    @{name="add_points_errors"; label="Add Points"; target=0.05},
    @{name="redeem_errors"; label="Redeem Reward"; target=0.10},
    @{name="balance_errors"; label="Check Balance"; target=0.05},
    @{name="http_req_failed"; label="Overall HTTP Failed"; target=0.10}
)

$errorMetrics | ForEach-Object {
    $metricName = $_.name
    $label = $_.label
    $target = $_.target
    
    if ($metrics.$metricName -and $metrics.$metricName.values) {
        $data = $metrics.$metricName.values
        $rate = [Math]::Round($data.rate * 100, 2)
        
        # Status
        $status = if ($data.rate -lt $target) { "✅ GOOD" } elseif ($data.rate -lt ($target * 1.5)) { "⚠️ ACCEPTABLE" } else { "❌ HIGH" }
        
        Write-Host ""
        Write-Host "  ❌ $label" -ForegroundColor White
        Write-Host "     Error Rate: $rate % $status" -ForegroundColor White
        Write-Host "     Target:     $([Math]::Round($target*100, 2)) %" -ForegroundColor Gray
    }
}

# ================================================================
# 3. THROUGHPUT ANALYSIS
# ================================================================
Write-Host ""
Write-Host ""
Write-Host "3️⃣  THROUGHPUT ANALYSIS" -ForegroundColor Yellow
Write-Host "─" * 64 -ForegroundColor Gray

$requestMetrics = @(
    @{name="add_points_total"; label="Add Points"},
    @{name="redeem_total"; label="Redeem Reward"},
    @{name="balance_total"; label="Check Balance"}
)

$totalRequests = 0
$requestMetrics | ForEach-Object {
    $metricName = $_.name
    $label = $_.label
    
    if ($metrics.$metricName -and $metrics.$metricName.value) {
        $count = $metrics.$metricName.value
        $totalRequests += $count
        Write-Host ""
        Write-Host "  📊 $label" -ForegroundColor White
        Write-Host "     Total Requests: $count" -ForegroundColor Gray
    }
}

if ($totalRequests -gt 0) {
    Write-Host ""
    Write-Host "  📊 Overall" -ForegroundColor White
    Write-Host "     Total Requests: $totalRequests" -ForegroundColor Gray
    
    # Get test duration from metrics
    if ($metrics.iteration_duration -and $metrics.iteration_duration.values) {
        $totalDuration = $totalRequests / ($metrics.iteration_duration.values.max * 1000) # rough estimate
        Write-Host "     Est. Throughput: ~$([Math]::Round($totalRequests / 60, 0)) req/s (assuming ~1 min test)" -ForegroundColor Gray
    }
}

# ================================================================
# 4. CONCURRENT USERS ANALYSIS
# ================================================================
Write-Host ""
Write-Host ""
Write-Host "4️⃣  CONCURRENT USERS ANALYSIS" -ForegroundColor Yellow
Write-Host "─" * 64 -ForegroundColor Gray

if ($metrics.active_vus -and $metrics.active_vus.values) {
    $activeVus = $metrics.active_vus.values
    Write-Host ""
    Write-Host "  👥 Concurrent Users (VUs)" -ForegroundColor White
    Write-Host "     Average:  $([Math]::Round($activeVus.avg, 0))" -ForegroundColor Gray
    Write-Host "     Max:      $([Math]::Round($activeVus.max, 0))" -ForegroundColor Gray
}

# ================================================================
# 5. SKENARIO KHUSUS
# ================================================================
Write-Host ""
Write-Host ""
Write-Host "5️⃣  SPECIAL SCENARIOS" -ForegroundColor Yellow
Write-Host "─" * 64 -ForegroundColor Gray

# Race condition
if ($metrics.race_condition_errors -and $metrics.race_condition_errors.values) {
    $raceErrors = $metrics.race_condition_errors.values
    $rate = [Math]::Round($raceErrors.rate * 100, 2)
    
    Write-Host ""
    Write-Host "  🔄 Race Condition Test" -ForegroundColor White
    Write-Host "     Error Rate: $rate %" -ForegroundColor Gray
    
    if ($raceErrors.rate -eq 0) {
        Write-Host "     Status: ✅ NO RACE CONDITION DETECTED" -ForegroundColor Green
    } else {
        Write-Host "     Status: ⚠️ POSSIBLE RACE CONDITION" -ForegroundColor Yellow
    }
}

# ================================================================
# 6. SUMMARY & RECOMMENDATIONS
# ================================================================
Write-Host ""
Write-Host ""
Write-Host "6️⃣  SUMMARY & RECOMMENDATIONS" -ForegroundColor Yellow
Write-Host "─" * 64 -ForegroundColor Gray

$issues = @()
$successes = @()

# Check response times
$responseTimeMetrics | ForEach-Object {
    $metricName = $_.name
    if ($metrics.$metricName -and $metrics.$metricName.values.p95 -gt $_.target * 1.5) {
        $issues += "Slow response time for $($_.label)"
    } elseif ($metrics.$metricName -and $metrics.$metricName.values.p95 -lt $_.target) {
        $successes += "$($_.label) response time is excellent"
    }
}

# Check error rates
$errorMetrics | ForEach-Object {
    $metricName = $_.name
    if ($metrics.$metricName -and $metrics.$metricName.values.rate -gt $_.target * 1.5) {
        $issues += "High error rate for $($_.label)"
    }
}

Write-Host ""

if ($successes.Count -gt 0) {
    Write-Host "✅ SUCCESSES:" -ForegroundColor Green
    $successes | ForEach-Object {
        Write-Host "  • $_" -ForegroundColor Green
    }
}

Write-Host ""

if ($issues.Count -eq 0) {
    Write-Host "✅ NO ISSUES DETECTED - SYSTEM PERFORMANCE IS GOOD!" -ForegroundColor Green
} else {
    Write-Host "⚠️ ISSUES DETECTED:" -ForegroundColor Yellow
    $issues | ForEach-Object {
        Write-Host "  • $_" -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "💡 RECOMMENDATIONS:" -ForegroundColor Cyan
    
    $issues | ForEach-Object {
        if ($_ -like "*response time*") {
            Write-Host "  → Optimize database queries" -ForegroundColor Cyan
            Write-Host "  → Add caching layer" -ForegroundColor Cyan
            Write-Host "  → Check for bottlenecks in controller" -ForegroundColor Cyan
        }
        if ($_ -like "*error rate*") {
            Write-Host "  → Investigate error logs" -ForegroundColor Cyan
            Write-Host "  → Verify data validation" -ForegroundColor Cyan
            Write-Host "  → Check resource limits" -ForegroundColor Cyan
        }
        if ($_ -like "*race condition*") {
            Write-Host "  → Implement pessimistic locking" -ForegroundColor Cyan
            Write-Host "  → Use database transactions" -ForegroundColor Cyan
            Write-Host "  → Review concurrency handling" -ForegroundColor Cyan
        }
    }
}

# ================================================================
# 7. EXPORT OPTIONS
# ================================================================
Write-Host ""
Write-Host ""
Write-Host "📥 EXPORT OPTIONS" -ForegroundColor Yellow
Write-Host "─" * 64 -ForegroundColor Gray

$exportFile = $ResultFile -replace "\.json$", "_report.txt"
$csvFile = $ResultFile -replace "\.json$", "_metrics.csv"

Write-Host ""
Write-Host "  Want to export analysis?" -ForegroundColor Cyan
Write-Host ""
Write-Host "  PowerShell Commands:" -ForegroundColor Gray
Write-Host "    # Export to CSV (for Excel)" -ForegroundColor Gray
Write-Host "    `$json.metrics | ConvertTo-Csv | Out-File '$csvFile'" -ForegroundColor White
Write-Host ""
Write-Host "    # Save this analysis to text file" -ForegroundColor Gray
Write-Host "    .\k6-analyze-results.ps1 | Out-File '$exportFile'" -ForegroundColor White

Write-Host ""
Write-Host "═" * 64 -ForegroundColor Cyan
Write-Host ""
Write-Host "✅ Analysis complete!" -ForegroundColor Green
Write-Host ""
