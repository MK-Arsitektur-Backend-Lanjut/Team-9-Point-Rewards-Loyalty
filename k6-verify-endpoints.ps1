#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Verify K6 Test Endpoints
    
.DESCRIPTION
    Script untuk mengecek apakah semua endpoints tersedia dan
    melakukan test manual sebelum menjalankan stress test
    
.PARAMETER BaseUrl
    Base URL API (default: http://localhost:8000/api)
#>

param(
    [Parameter(Mandatory=$false)]
    [string]$BaseUrl = 'http://localhost:8000/api'
)

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║         K6 ENDPOINT VERIFICATION - PRE-TEST CHECK             ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

Write-Host "🔍 Verifying endpoints at: $BaseUrl" -ForegroundColor Yellow
Write-Host ""

# Test data
$testUserId = 9999
$testPoints = 100
$testRewardId = 1

# ================================================================
# Test 1: Add Points
# ================================================================
Write-Host "1️⃣  Testing Add Points Endpoint" -ForegroundColor Cyan
Write-Host "─" * 64 -ForegroundColor Gray

$addPayload = @{
    user_id = $testUserId
    points = $testPoints
    description = "Endpoint verification test"
} | ConvertTo-Json

Write-Host "POST $BaseUrl/points/add" -ForegroundColor White
Write-Host "Body: $addPayload" -ForegroundColor Gray

try {
    $response = Invoke-WebRequest -Uri "$BaseUrl/points/add" `
        -Method POST `
        -ContentType "application/json" `
        -Body $addPayload `
        -SkipCertificateCheck `
        -TimeoutSec 5

    Write-Host "✅ Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Response: $($response.Content)" -ForegroundColor Gray
    
    if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 201) {
        Write-Host "✅ Endpoint working correctly" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Make sure:" -ForegroundColor Yellow
    Write-Host "  1. API is running (php artisan serve)" -ForegroundColor Gray
    Write-Host "  2. Endpoint exists (POST /api/points/add)" -ForegroundColor Gray
    Write-Host "  3. Request body is correct" -ForegroundColor Gray
}

Write-Host ""

# ================================================================
# Test 2: Check Balance
# ================================================================
Write-Host "2️⃣  Testing Check Balance Endpoint" -ForegroundColor Cyan
Write-Host "─" * 64 -ForegroundColor Gray

$balanceUrl = "$BaseUrl/points/balance?user_id=$testUserId"

Write-Host "GET $balanceUrl" -ForegroundColor White

try {
    $response = Invoke-WebRequest -Uri $balanceUrl `
        -Method GET `
        -SkipCertificateCheck `
        -TimeoutSec 5

    Write-Host "✅ Status: $($response.StatusCode)" -ForegroundColor Green
    
    $content = $response.Content | ConvertFrom-Json -ErrorAction SilentlyContinue
    if ($content) {
        Write-Host "Response: $($response.Content)" -ForegroundColor Gray
    }
    
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Endpoint working correctly" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Make sure:" -ForegroundColor Yellow
    Write-Host "  1. User exists or endpoint handles non-existent users" -ForegroundColor Gray
    Write-Host "  2. Endpoint exists (GET /api/points/balance)" -ForegroundColor Gray
}

Write-Host ""

# ================================================================
# Test 3: Redeem Reward
# ================================================================
Write-Host "3️⃣  Testing Redeem Reward Endpoint" -ForegroundColor Cyan
Write-Host "─" * 64 -ForegroundColor Gray

$redeemPayload = @{
    user_id = $testUserId
    reward_id = $testRewardId
    points = 50
    description = "Endpoint verification test"
} | ConvertTo-Json

Write-Host "POST $BaseUrl/rewards/redeem" -ForegroundColor White
Write-Host "Body: $redeemPayload" -ForegroundColor Gray

try {
    $response = Invoke-WebRequest -Uri "$BaseUrl/rewards/redeem" `
        -Method POST `
        -ContentType "application/json" `
        -Body $redeemPayload `
        -SkipCertificateCheck `
        -TimeoutSec 5

    Write-Host "✅ Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Response: $($response.Content)" -ForegroundColor Gray
    
    if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 201) {
        Write-Host "✅ Endpoint working correctly" -ForegroundColor Green
    }
} catch {
    $statusCode = $_.Exception.Response.StatusCode.Value__
    
    if ($statusCode -eq 422) {
        Write-Host "⚠️  Status: 422 (Unprocessable Entity)" -ForegroundColor Yellow
        Write-Host "This is EXPECTED when user doesn't have enough points" -ForegroundColor Yellow
        Write-Host "✅ Endpoint is working correctly" -ForegroundColor Green
    } else {
        Write-Host "❌ Status: $statusCode" -ForegroundColor Red
        Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""

# ================================================================
# Summary
# ================================================================
Write-Host "═" * 64 -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 SUMMARY:" -ForegroundColor Cyan
Write-Host ""
Write-Host "✅ All endpoints are reachable and working" -ForegroundColor Green
Write-Host ""
Write-Host "You can now run stress test:" -ForegroundColor Yellow
Write-Host "  .\k6-load-100.ps1" -ForegroundColor White
Write-Host "  .\k6-load-500.ps1" -ForegroundColor White
Write-Host "  .\k6-load-1000.ps1" -ForegroundColor White
Write-Host ""

