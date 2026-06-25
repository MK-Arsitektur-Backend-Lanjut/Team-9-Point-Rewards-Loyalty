<?php

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AuthController;
use App\Models\ActivityRule;
use App\Models\MembershipTier;
use App\Models\PointActivityLog;
use App\Models\ReferralLog;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    if (! Auth::check()) {
        return view('auth.login');
    }

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
        'users' => User::query()
            ->leftJoin('point_balances', 'users.id', '=', 'point_balances.user_id')
            ->select(
                'users.id',
                'users.name',
                \Illuminate\Support\Facades\DB::raw('COALESCE(point_balances.current_balance, 0) as points'),
                'users.membership_tier_id',
                'users.referral_code'
            )
            ->orderBy('users.id')
            ->limit(12)
            ->get(),
        'activityRules' => ActivityRule::query()->select('id', 'activity_code', 'point_value', 'is_active')->orderBy('id')->limit(12)->get(),
        'rewards' => Reward::query()->select('id', 'name', 'points_required', 'stock', 'is_physical')->orderBy('id')->limit(12)->get(),
        'tiers' => $hasMembershipTiers
            ? MembershipTier::query()->select('id', 'code', 'name', 'min_points', 'max_points', 'point_multiplier')->orderBy('min_points')->get()
            : collect(),
        'recentLogs' => PointActivityLog::query()->select('id', 'user_id', 'activity_code', 'points_earned', 'earned_at')->latest('id')->limit(8)->get(),
    ]);
});

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [AccountController::class, 'profile'])->name('user.profile');
    Route::post('/profile', [AccountController::class, 'updateProfile'])->name('user.profile.update');
    Route::post('/password', [AccountController::class, 'updatePassword'])->name('user.password.update');
    Route::get('/statement', [AccountController::class, 'statement'])->name('user.statement');
});
