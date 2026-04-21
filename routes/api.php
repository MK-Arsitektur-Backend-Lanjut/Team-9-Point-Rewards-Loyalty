<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityRuleController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\StatementController;


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
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['jwt.auth'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Statement & Points
    Route::get('/statement', [StatementController::class, 'index']);
    Route::get('/statement/export-pdf', [StatementController::class, 'exportPdf']);
    Route::get('/points/balance', [StatementController::class, 'balance']);
});

Route::apiResource('activity-rules', ActivityRuleController::class)->except(['show']);
Route::apiResource('rewards', RewardController::class)->except(['show']);
Route::post('rewards/{reward}/decrement-stock', [RewardController::class, 'decrementStock']);
Route::post('/activity/trigger', [ActivityRuleController::class, 'trigger']);

Route::get('membership/tiers', [MembershipController::class, 'listTiers']);
Route::post('membership/tiers', [MembershipController::class, 'createTier']);
Route::put('membership/tiers/{membershipTier}', [MembershipController::class, 'updateTier']);
Route::delete('membership/tiers/{membershipTier}', [MembershipController::class, 'deleteTier']);
Route::post('membership/tiers/recalculate', [MembershipController::class, 'recalculateTier']);

Route::post('membership/referrals/generate', [MembershipController::class, 'generateReferralCode']);
Route::post('membership/referrals/apply', [MembershipController::class, 'applyReferral']);

Route::post('membership/activity/trigger', [MembershipController::class, 'triggerActivity']);
Route::post('membership/rewards/{reward}/redeem', [MembershipController::class, 'redeemReward']);
