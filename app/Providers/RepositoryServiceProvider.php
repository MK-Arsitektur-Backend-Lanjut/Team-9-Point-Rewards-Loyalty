<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Contracts\PointActivityLogRepositoryInterface;
use App\Repositories\Eloquent\PointActivityLogRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(PointActivityLogRepositoryInterface::class, PointActivityLogRepository::class);
    }

    public function boot(): void
    {
        //
    }
}