<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\PointBalanceRepositoryContract;
use App\Repositories\Contracts\PointLogRepositoryContract;
use App\Repositories\Contracts\PointRuleRepositoryContract;
use App\Repositories\Contracts\ReferralRepositoryContract;
use App\Repositories\PointBalanceRepository;
use App\Repositories\PointLogRepository;
use App\Repositories\PointRuleRepository;
use App\Repositories\ReferralRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Repository Bindings
        $this->app->bind(
            PointBalanceRepositoryContract::class,
            PointBalanceRepository::class
        );

        $this->app->bind(
            PointLogRepositoryContract::class,
            PointLogRepository::class
        );

        $this->app->bind(
            PointRuleRepositoryContract::class,
            PointRuleRepository::class
        );

        $this->app->bind(
            ReferralRepositoryContract::class,
            ReferralRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);
    }
}
