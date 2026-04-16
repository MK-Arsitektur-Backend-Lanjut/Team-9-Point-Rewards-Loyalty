<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RewardProcessingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Reward Processing Module Routes
Route::prefix('rewards')->group(function () {
    // Add points automatically
    Route::post('/add-points', [RewardProcessingController::class, 'addPoints'])
        ->name('rewards.add');

    // Redeem points
    Route::post('/redeem', [RewardProcessingController::class, 'redeemPoints'])
        ->name('rewards.redeem');

    // Get balance
    Route::get('/balance/{userId}', [RewardProcessingController::class, 'getBalance'])
        ->name('rewards.balance');

    // Validate balance
    Route::post('/validate-balance', [RewardProcessingController::class, 'validateBalance'])
        ->name('rewards.validate');

    // Get user point logs
    Route::get('/logs/{userId}', [RewardProcessingController::class, 'getLogs'])
        ->name('rewards.logs');

    // Get all logs with filters
    Route::get('/all-logs', [RewardProcessingController::class, 'getAllLogs'])
        ->name('rewards.all-logs');
});
