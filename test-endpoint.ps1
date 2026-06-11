# Simple test - lihat actual response dari API
$url = "http://localhost:8000/api/demo/rewards/balance/2"

Write-Host "`nFetching: $url`n" -ForegroundColor Cyan

$response = Invoke-WebRequest -Uri $url -Method GET -UseBasicParsing

Write-Host "HTTP Status: $($response.StatusCode)" -ForegroundColor Yellow
Write-Host "`nRaw Response:" -ForegroundColor Yellow
Write-Host $response.Content -ForegroundColor White

$json = $response.Content | ConvertFrom-Json
Write-Host "`nParsed JSON:" -ForegroundColor Yellow
Write-Host ($json | ConvertTo-Json -Depth 10) -ForegroundColor Green

Write-Host "`nBalance Value: $($json.balance)" -ForegroundColor Cyan
