<?php

namespace App\Providers;

use App\Repositories\Contracts\ActivityRuleRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Repositories\Eloquent\ActivityRuleRepository;
use App\Repositories\Eloquent\RewardRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ActivityRuleRepositoryInterface::class, ActivityRuleRepository::class);
        $this->app->bind(RewardRepositoryInterface::class, RewardRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
