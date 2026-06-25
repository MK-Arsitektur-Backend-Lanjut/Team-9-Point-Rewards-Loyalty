#!/usr/bin/env pwsh
# Testing Script untuk Reward Optimization Demo

$baseUrl = "http://localhost:8000/api/demo/rewards"
$userId = 2

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Reward Optimization Demo - Testing Script" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Add Points
Write-Host "Test 1: Add 100 Points" -ForegroundColor Yellow
$body = @{
    user_id = $userId
    points = 100
    description = "Test from PowerShell"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/add" `
        -Method POST `
        -ContentType "application/json" `
        -Body $body `
        -ErrorAction Stop
    
    $result = $response.Content | ConvertFrom-Json
    if ($result.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "Points Added: $($result.data.points_added)" -ForegroundColor Green
        Write-Host "Current Balance: $($result.data.current_balance)" -ForegroundColor Green
    } else {
        Write-Host "❌ FAILED: $($result.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $_" -ForegroundColor Red
}
Write-Host ""

# Test 2: Check Balance
Write-Host "Test 2: Check Balance" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/balance/$userId" -Method GET -ErrorAction Stop
    $result = $response.Content | ConvertFrom-Json
    
    if ($result.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "User: $($result.user_name)" -ForegroundColor Green
        Write-Host "Current Balance: $($result.balance)" -ForegroundColor Green
    } else {
        Write-Host "❌ FAILED: $($result.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $_" -ForegroundColor Red
}
Write-Host ""

# Test 3: Validate Balance
Write-Host "Test 3: Validate Balance (Check if has 50 points)" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/validate/$userId/50" -Method GET -ErrorAction Stop
    $result = $response.Content | ConvertFrom-Json
    
    if ($result.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "Has Enough Points: $($result.has_enough_points)" -ForegroundColor Green
    } else {
        Write-Host "❌ FAILED: $($result.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $_" -ForegroundColor Red
}
Write-Host ""

# Test 4: Redeem Points
Write-Host "Test 4: Redeem 30 Points" -ForegroundColor Yellow
$body = @{
    user_id = $userId
    points = 30
    description = "Test redemption"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/redeem" `
        -Method POST `
        -ContentType "application/json" `
        -Body $body `
        -ErrorAction Stop
    
    $result = $response.Content | ConvertFrom-Json
    if ($result.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "Points Redeemed: $($result.data.points_redeemed)" -ForegroundColor Green
        Write-Host "Balance After: $($result.data.current_balance)" -ForegroundColor Green
    } else {
        Write-Host "❌ FAILED: $($result.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $_" -ForegroundColor Red
}
Write-Host ""

# Test 5: Get Detailed Info
Write-Host "Test 5: Get Detailed Balance Info" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/details/$userId" -Method GET -ErrorAction Stop
    $result = $response.Content | ConvertFrom-Json
    
    if ($result.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host "Current Balance: $($result.current_balance)" -ForegroundColor Green
        Write-Host "Lifetime Points: $($result.lifetime_points)" -ForegroundColor Green
        Write-Host "Expired Points: $($result.expired_points)" -ForegroundColor Green
        Write-Host "Locked Points: $($result.locked_points)" -ForegroundColor Green
    } else {
        Write-Host "❌ FAILED: $($result.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $_" -ForegroundColor Red
}
Write-Host ""

# Test 6: Performance Demo (THE MAIN EVENT!)
Write-Host "Test 6: Performance Comparison 🚀" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/performance" -Method GET -ErrorAction Stop
    $result = $response.Content | ConvertFrom-Json
    
    if ($result.success) {
        Write-Host "✅ SUCCESS" -ForegroundColor Green
        Write-Host ""
        Write-Host "Performance Metrics:" -ForegroundColor Yellow
        Write-Host "  OLD Way (SUM Aggregate):   $($result.performance.old_way_sum_aggregate_ms) ms" -ForegroundColor Red
        Write-Host "  NEW Way (Index Lookup):    $($result.performance.new_way_index_lookup_ms) ms" -ForegroundColor Green
        Write-Host "  Speed Gain:                $($result.performance.speed_gain_x_times)x faster! 🚀" -ForegroundColor Cyan
        Write-Host "  Result:                    $($result.performance.result)" -ForegroundColor Green
    } else {
        Write-Host "❌ FAILED: $($result.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $_" -ForegroundColor Red
}
Write-Host ""

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Testing Complete!" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
