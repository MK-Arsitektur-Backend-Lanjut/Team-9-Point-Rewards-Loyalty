<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityRule;
use App\Repositories\Contracts\ActivityRuleReadRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ActivityRuleReadRepository implements ActivityRuleReadRepositoryInterface
{
    public function findActiveByCode(string $activityCode): ?ActivityRule
    {
        $cacheKey = "activity_rule_{$activityCode}";
        
        return Cache::remember($cacheKey, 3600, function () use ($activityCode) {
            return ActivityRule::query()
                ->where('activity_code', $activityCode)
                ->where('is_active', true)
                ->first();
        });
    }
}
