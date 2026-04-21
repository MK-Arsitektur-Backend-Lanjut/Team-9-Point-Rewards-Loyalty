<?php

namespace App\Providers;

use App\Repositories\Contracts\ActivityRuleReadRepositoryInterface;
use App\Repositories\Contracts\ActivityRuleRepositoryInterface;
use App\Repositories\Contracts\MembershipTierRepositoryInterface;
use App\Repositories\Contracts\ReferralLogRepositoryInterface;
use App\Repositories\Contracts\RewardRedemptionRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\ActivityRuleReadRepository;
use App\Repositories\Eloquent\ActivityRuleRepository;
use App\Repositories\Eloquent\MembershipTierRepository;
use App\Repositories\Eloquent\ReferralLogRepository;
use App\Repositories\Eloquent\RewardRedemptionRepository;
use App\Repositories\Eloquent\RewardRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActivityRuleRepositoryInterface::class, ActivityRuleRepository::class);
        $this->app->bind(RewardRepositoryInterface::class, RewardRepository::class);
        $this->app->bind(ActivityRuleReadRepositoryInterface::class, ActivityRuleReadRepository::class);
        $this->app->bind(MembershipTierRepositoryInterface::class, MembershipTierRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ReferralLogRepositoryInterface::class, ReferralLogRepository::class);
        $this->app->bind(RewardRedemptionRepositoryInterface::class, RewardRedemptionRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
