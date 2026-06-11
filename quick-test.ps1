#!/usr/bin/env pwsh

$baseUrl = "http://localhost:8000/api/demo/rewards"

Write-Host "`n=== QUICK TEST - Check localhost:8000 Response ===" -ForegroundColor Cyan

# Test 1: Get Balance
Write-Host "`nTest 1: GET /balance/2" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/balance/2" `
        -Method GET `
        -ContentType "application/json" `
        -UseBasicParsing

    $data = $response.Content | ConvertFrom-Json
    
    Write-Host "SUCCESS - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Balance Value: $($data.balance)" -ForegroundColor Cyan
    Write-Host ($data | ConvertTo-Json -Depth 10) -ForegroundColor White
}
catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Get Details
Write-Host "`nTest 2: GET /details/2" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/details/2" `
        -Method GET `
        -ContentType "application/json" `
        -UseBasicParsing

    $data = $response.Content | ConvertFrom-Json
    
    Write-Host "SUCCESS - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Current Balance: $($data.current_balance)" -ForegroundColor Cyan
    Write-Host ($data | ConvertTo-Json -Depth 10) -ForegroundColor White
}
catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Check Database Directly
Write-Host "`nTest 3: Database Check" -ForegroundColor Yellow
$dbResult = docker-compose exec db mysql -u root -proot loyalty_db -e "SELECT user_id, current_balance FROM point_balances WHERE user_id=2;"
Write-Host "Database Result:" -ForegroundColor Green
Write-Host ($dbResult | Out-String) -ForegroundColor White

Write-Host "`n=== Result Summary ===" -ForegroundColor Cyan
Write-Host "- If current_balance = 70: ALL WORKING!" -ForegroundColor Green
Write-Host "- Browser cache: Ctrl+Shift+Del" -ForegroundColor Magenta
Write-Host "====================`n" -ForegroundColor Cyan
