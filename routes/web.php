<?php

use App\Models\ActivityRule;
use App\Models\MembershipTier;
use App\Models\PointActivityLog;
use App\Models\ReferralLog;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $hasMembershipTiers = Schema::hasTable('membership_tiers');
    $hasReferralLogs = Schema::hasTable('referral_logs');
    $hasRewardRedemptions = Schema::hasTable('reward_redemptions');

    return view('welcome', [
        'stats' => [
            'users' => User::query()->count(),
            'activity_rules' => ActivityRule::query()->count(),
            'rewards' => Reward::query()->count(),
            'activity_logs' => PointActivityLog::query()->count(),
            'tiers' => $hasMembershipTiers ? MembershipTier::query()->count() : 0,
            'referrals' => $hasReferralLogs ? ReferralLog::query()->count() : 0,
            'redemptions' => $hasRewardRedemptions ? RewardRedemption::query()->count() : 0,
        ],
        'users' => User::query()->select('id', 'name', 'points', 'membership_tier_id', 'referral_code')->orderBy('id')->limit(12)->get(),
        'activityRules' => ActivityRule::query()->select('id', 'activity_code', 'point_value', 'is_active')->orderBy('id')->limit(12)->get(),
        'rewards' => Reward::query()->select('id', 'name', 'points_required', 'stock', 'is_physical')->orderBy('id')->limit(12)->get(),
        'tiers' => $hasMembershipTiers
            ? MembershipTier::query()->select('id', 'code', 'name', 'min_points', 'max_points', 'point_multiplier')->orderBy('min_points')->get()
            : collect(),
        'recentLogs' => PointActivityLog::query()->select('id', 'user_id', 'activity_code', 'points_earned', 'earned_at')->latest('id')->limit(8)->get(),
    ]);
});
