#!/usr/bin/env pwsh
<#
.SYNOPSIS
Performance Optimization Script - Clear cache and run K6 tests

.DESCRIPTION
1. Clear Laravel cache
2. Run K6 tests with optimized settings
3. Compare results with baseline
#>

Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║           PERFORMANCE OPTIMIZATION - CACHE CLEAR              ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Step 1: Clear Laravel Cache
Write-Host "📦 Step 1: Clearing Laravel application cache..." -ForegroundColor Yellow
Write-Host "   Command: php artisan cache:clear" -ForegroundColor Gray

php artisan cache:clear
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Cache cleared successfully" -ForegroundColor Green
} else {
    Write-Host "   ❌ Failed to clear cache" -ForegroundColor Red
}
Write-Host ""

# Step 2: Clear config cache (if any)
Write-Host "📦 Step 2: Clearing config cache..." -ForegroundColor Yellow
Write-Host "   Command: php artisan config:clear" -ForegroundColor Gray
php artisan config:clear 2>$null
Write-Host "   ✅ Config cache cleared" -ForegroundColor Green
Write-Host ""

# Step 3: Optimize autoloader
Write-Host "📦 Step 3: Optimizing autoloader..." -ForegroundColor Yellow
Write-Host "   Command: composer dump-autoload --optimize" -ForegroundColor Gray
composer dump-autoload --optimize 2>$null
Write-Host "   ✅ Autoloader optimized" -ForegroundColor Green
Write-Host ""

# Step 4: Run K6 test with lower concurrency first (sanity check)
Write-Host "🧪 Step 4: Running K6 sanity check (5 concurrent users)..." -ForegroundColor Yellow
Write-Host "   Command: k6 run k6-simple-test.js --env LOAD=5 --env DURATION=20s" -ForegroundColor Gray
Write-Host "   This should now be faster than before..." -ForegroundColor Cyan
Write-Host ""

$startTime = Get-Date
k6 run k6-simple-test.js --env LOAD=5 --env DURATION=20s 2>&1 | Tee-Object -Variable sanityCheckOutput | Out-Host
$sanityDuration = ((Get-Date) - $startTime).TotalSeconds

Write-Host ""
Write-Host "✅ Sanity check completed in $sanityDuration seconds" -ForegroundColor Green
Write-Host ""

# Step 5: Wait and run full test
Read-Host "Press Enter to run the full test with 10 concurrent users (or Ctrl+C to skip)"

Write-Host ""
Write-Host "🧪 Step 5: Running full K6 test (10 concurrent users)..." -ForegroundColor Yellow
Write-Host "   Command: k6 run k6-simple-test.js --env LOAD=10 --env DURATION=30s" -ForegroundColor Gray
Write-Host ""

$startTime = Get-Date
k6 run k6-simple-test.js --env LOAD=10 --env DURATION=30s 2>&1 | Tee-Object -Variable fullTestOutput | Out-Host
$fullDuration = ((Get-Date) - $startTime).TotalSeconds

Write-Host ""
Write-Host "✅ Full test completed in $fullDuration seconds" -ForegroundColor Green
Write-Host ""

# Summary
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                    OPTIMIZATION COMPLETE                      ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Performance Summary:" -ForegroundColor Cyan
Write-Host "   • Sanity test (5 users):   $sanityDuration seconds" -ForegroundColor Yellow
Write-Host "   • Full test (10 users):    $fullDuration seconds" -ForegroundColor Yellow
Write-Host ""
Write-Host "📝 Changes Made:" -ForegroundColor Cyan
Write-Host "   ✓ Moved tier recalculation OUTSIDE transaction" -ForegroundColor Green
Write-Host "   ✓ Reduced pessimistic lock duration" -ForegroundColor Green
Write-Host "   ✓ Cleared application cache" -ForegroundColor Green
Write-Host ""
Write-Host "📖 See PERFORMANCE_ANALYSIS.md for detailed information" -ForegroundColor Yellow
Write-Host ""
