<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityRuleController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\RewardController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
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
