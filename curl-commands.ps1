# ================================================================
# CURL Commands untuk Manage Points di localhost:8000
# ================================================================

# BASE URL
$base = "http://localhost:8000/api/demo/rewards"

# ================================================================
# 1. TAMBAH POINTS
# ================================================================
Write-Host "`n=== TAMBAH POINTS ===" -ForegroundColor Cyan
Write-Host 'curl -X POST http://localhost:8000/api/demo/rewards/add `' -ForegroundColor Yellow
Write-Host '  -H "Content-Type: application/json" `' -ForegroundColor Yellow
Write-Host '  -d "{\"user_id\": 2, \"points\": 50, \"description\": \"Bonus points\"}"' -ForegroundColor Yellow

Write-Host "`nAtau gunakan PowerShell:" -ForegroundColor Green
Write-Host '$body = @{ user_id = 2; points = 50; description = "Bonus" } | ConvertTo-Json' -ForegroundColor White
Write-Host '$response = Invoke-WebRequest -Uri http://localhost:8000/api/demo/rewards/add `' -ForegroundColor White
Write-Host '  -Method POST -ContentType "application/json" -Body $body -UseBasicParsing' -ForegroundColor White
Write-Host '$response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10' -ForegroundColor White

# ================================================================
# 2. REDEEM POINTS
# ================================================================
Write-Host "`n=== REDEEM POINTS ===" -ForegroundColor Cyan
Write-Host 'curl -X POST http://localhost:8000/api/demo/rewards/redeem `' -ForegroundColor Yellow
Write-Host '  -H "Content-Type: application/json" `' -ForegroundColor Yellow
Write-Host '  -d "{\"user_id\": 2, \"points\": 20, \"description\": \"Redeem for discount\"}"' -ForegroundColor Yellow

Write-Host "`nAtau gunakan PowerShell:" -ForegroundColor Green
Write-Host '$body = @{ user_id = 2; points = 20; description = "Redeem" } | ConvertTo-Json' -ForegroundColor White
Write-Host '$response = Invoke-WebRequest -Uri http://localhost:8000/api/demo/rewards/redeem `' -ForegroundColor White
Write-Host '  -Method POST -ContentType "application/json" -Body $body -UseBasicParsing' -ForegroundColor White
Write-Host '$response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10' -ForegroundColor White

# ================================================================
# 3. CHECK BALANCE
# ================================================================
Write-Host "`n=== CHECK BALANCE ===" -ForegroundColor Cyan
Write-Host 'curl -X GET http://localhost:8000/api/demo/rewards/balance/2' -ForegroundColor Yellow

Write-Host "`nAtau gunakan PowerShell:" -ForegroundColor Green
Write-Host '$response = Invoke-WebRequest -Uri http://localhost:8000/api/demo/rewards/balance/2 -UseBasicParsing' -ForegroundColor White
Write-Host '$response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10' -ForegroundColor White

# ================================================================
# 4. LIHAT DETAIL BALANCE
# ================================================================
Write-Host "`n=== LIHAT DETAIL BALANCE ===" -ForegroundColor Cyan
Write-Host 'curl -X GET http://localhost:8000/api/demo/rewards/details/2' -ForegroundColor Yellow

# ================================================================
# ACTUAL EXECUTION - Uncomment untuk jalankan
# ================================================================
Write-Host "`n`n=== LIVE EXECUTION ===" -ForegroundColor Magenta
Write-Host "(Uncomment bagian di bawah untuk execute)" -ForegroundColor Gray

<#
# Test 1: Add 50 points
Write-Host "`nTest 1: Add 50 points" -ForegroundColor Yellow
$body = @{ user_id = 2; points = 50; description = "Test add" } | ConvertTo-Json
$response = Invoke-WebRequest -Uri "$base/add" -Method POST -ContentType "application/json" -Body $body -UseBasicParsing
$response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10

# Test 2: Check balance
Write-Host "`nTest 2: Check balance" -ForegroundColor Yellow
$response = Invoke-WebRequest -Uri "$base/balance/2" -UseBasicParsing
$response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10

# Test 3: Redeem 20 points
Write-Host "`nTest 3: Redeem 20 points" -ForegroundColor Yellow
$body = @{ user_id = 2; points = 20; description = "Test redeem" } | ConvertTo-Json
$response = Invoke-WebRequest -Uri "$base/redeem" -Method POST -ContentType "application/json" -Body $body -UseBasicParsing
$response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10

# Test 4: Check balance after redeem
Write-Host "`nTest 4: Check balance after redeem" -ForegroundColor Yellow
$response = Invoke-WebRequest -Uri "$base/balance/2" -UseBasicParsing
$response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10
#>

Write-Host "`n" -ForegroundColor Green
