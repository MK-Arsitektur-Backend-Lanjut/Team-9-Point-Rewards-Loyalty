<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityRule;
use App\Repositories\Contracts\ActivityRuleReadRepositoryInterface;

class ActivityRuleReadRepository implements ActivityRuleReadRepositoryInterface
{
    public function findActiveByCode(string $activityCode): ?ActivityRule
    {
        return ActivityRule::query()
            ->where('activity_code', $activityCode)
            ->where('is_active', true)
            ->first();
    }
}
