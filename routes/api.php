<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityRuleController;
use App\Http\Controllers\Api\RewardController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('activity-rules', ActivityRuleController::class)->except(['show']);
Route::apiResource('rewards', RewardController::class)->except(['show']);
Route::post('rewards/{reward}/decrement-stock', [RewardController::class, 'decrementStock']);
Route::post('/activity/trigger', [ActivityRuleController::class, 'trigger']);