#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Analyze K6 Test Results - Referral & Tiering System
#>

param(
    [string]$ResultFile,
    [ValidateSet('normal','race_condition','mixed','')]
    [string]$Scenario = ''
)

# ================================================================
# INIT PATH SAFE
# ================================================================
$baseDir = if ($PSScriptRoot) { $PSScriptRoot } else { Get-Location }
$resultsDir = Join-Path $baseDir "results"

# ================================================================
# HEADER
# ================================================================
Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "     K6 RESULTS ANALYZER - REFERRAL & TIERING SYSTEM" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# ================================================================
# RESOLVE FILE
# ================================================================
if (-not $ResultFile) {

    $pattern = if ($Scenario) { "${Scenario}_load*.json" } else { "*.json" }

    $files = Get-ChildItem -Path $resultsDir -Filter $pattern -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -notmatch '_report' } |
        Sort-Object LastWriteTime -Descending

    if (-not $files -or $files.Count -eq 0) {
        Write-Host "[ERROR] No result files found in: $resultsDir" -ForegroundColor Red
        Write-Host "Run stress test first:" -ForegroundColor Yellow
        Write-Host "  .\k6\k6-run-referral-tiering.ps1"
        exit 1
    }

    $ResultFile = $files[0].FullName

    Write-Host "[INFO] Using file: $($files[0].Name)" -ForegroundColor Green
    Write-Host "       Modified : $($files[0].LastWriteTime)" -ForegroundColor Gray

    if ($files[0].Name -match '^(\w+)_load(\d+)_') {
        Write-Host "       Scenario : $($Matches[1])" -ForegroundColor Gray
        Write-Host "       Load     : $($Matches[2]) VUs" -ForegroundColor Gray
    }

    Write-Host ""
}

if (-not (Test-Path $ResultFile)) {
    Write-Host "[ERROR] File not found: $ResultFile" -ForegroundColor Red
    exit 1
}

# ================================================================
# LOAD JSON
# ================================================================
try {
    $json    = Get-Content $ResultFile -Raw | ConvertFrom-Json
    $metrics = $json.metrics
}
catch {
    Write-Host "[ERROR] Failed to parse JSON: $_" -ForegroundColor Red
    exit 1
}

if (-not $metrics) {
    Write-Host "[ERROR] No 'metrics' field found" -ForegroundColor Red
    exit 1
}

# ================================================================
# HELPERS
# ================================================================
function Write-Line {
    Write-Host ("-" * 60) -ForegroundColor DarkGray
}

function Get-Status {
    param($value, $good, $warn)
    if ($value -le $good) { return "GOOD" }
    elseif ($value -le $warn) { return "OK" }
    else { return "BAD" }
}

function Show-Response {
    param($label, $metric, $tAvg, $tP95, $tP99)

    $m = $metrics.$metric
    if (-not $m -or -not $m.values) {
        Write-Host "  $label : no data" -ForegroundColor DarkGray
        return $null
    }

    $avg = [math]::Round($m.values.avg, 2)
    $p95 = [math]::Round($m.values.'p(95)', 2)
    $p99 = [math]::Round($m.values.'p(99)', 2)

    $status = Get-Status $p95 $tP95 ($tP95 * 1.5)

    Write-Host ""
    Write-Host "  $label" -ForegroundColor White
    Write-Host ("    avg: {0} ms | p95: {1} ms | p99: {2} ms [{3}]" -f $avg, $p95, $p99, $status)

    return @{ label=$label; p95=$p95; target=$tP95 }
}

function Show-Error {
    param($label, $metric, $target)

    $m = $metrics.$metric
    if (-not $m -or -not $m.values) { return $null }

    $rate = $m.values.rate
    $pct  = [math]::Round($rate * 100, 2)

    $status = Get-Status $rate $target ($target * 1.5)

    Write-Host ("  {0} : {1}% [{2}]" -f $label, $pct, $status)

    return @{ label=$label; rate=$rate; target=$target }
}

function Show-Count {
    param($label, $metric)

    $m = $metrics.$metric
    if (-not $m) { return 0 }

    $count = if ($m.values) { $m.values.count } else { $m.value }
    Write-Host ("  {0,-30}: {1,8}" -f $label, $count)
    return [int]$count
}

# ================================================================
# RESPONSE TIME
# ================================================================
Write-Host "[1] RESPONSE TIME" -ForegroundColor Yellow
Write-Line

$rt = @()
$rt += Show-Response "Generate Referral" "generate_referral_duration" 300 500 1000
$rt += Show-Response "Apply Referral"    "apply_referral_duration"    500 800 1500
$rt += Show-Response "Recalculate Tier"  "recalculate_tier_duration"  600 1000 2000
$rt += Show-Response "HTTP Overall"      "http_req_duration"          500 2000 3000

# ================================================================
# ERROR RATE
# ================================================================
Write-Host ""
Write-Host "[2] ERROR RATE" -ForegroundColor Yellow
Write-Line

$er = @()
$er += Show-Error "Generate Error" "generate_referral_errors" 0.05
$er += Show-Error "Apply Error"    "apply_referral_errors"    0.10
$er += Show-Error "HTTP Failed"    "http_req_failed"          0.10

# ================================================================
# THROUGHPUT
# ================================================================
Write-Host ""
Write-Host "[3] THROUGHPUT" -ForegroundColor Yellow
Write-Line

$total = 0
$total += Show-Count "Generate Referral" "generate_referral_total"
$total += Show-Count "Apply Referral"    "apply_referral_total"

Write-Host ""
Write-Host ("  TOTAL REQUESTS : {0}" -f $total) -ForegroundColor White

if ($total -gt 0) {
    Write-Host ("  THROUGHPUT    : ~{0} req/s" -f [math]::Round($total/60,1))
}

# ================================================================
# SUMMARY
# ================================================================
Write-Host ""
Write-Host "[4] SUMMARY" -ForegroundColor Yellow
Write-Line

$issues = @()

foreach ($r in $rt) {
    if ($null -eq $r) { continue }
    if ($r.p95 -gt $r.target * 1.5) {
        $issues += "$($r.label) slow (p95=$($r.p95)ms)"
    }
}

foreach ($e in $er) {
    if ($null -eq $e) { continue }
    if ($e.rate -gt $e.target * 1.5) {
        $issues += "$($e.label) high error rate"
    }
}

if ($issues.Count -eq 0) {
    Write-Host "[OK] System performance is GOOD" -ForegroundColor Green
} else {
    Write-Host "[WARN] Issues detected:" -ForegroundColor Yellow
    foreach ($i in $issues) {
        Write-Host "  - $i"
    }
}

# ================================================================
# EXPORT
# ================================================================
$reportFile = $ResultFile -replace '\.json$', '_report.txt'

Write-Host ""
Write-Host "[EXPORT]" -ForegroundColor Yellow
Write-Line
Write-Host "Save report:"
Write-Host "  .\\k6\\k6-analyze-referral-tiering.ps1 | Out-File '$reportFile'"

Write-Host ""
Write-Host "[DONE] Analysis complete" -ForegroundColor Green
Write-Host ""
``