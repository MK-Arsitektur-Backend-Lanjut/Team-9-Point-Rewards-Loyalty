<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityRule;
use App\Repositories\Contracts\ActivityRuleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ActivityRuleRepository implements ActivityRuleRepositoryInterface
{
    protected string $cacheKey = 'activity_rules.all';

    public function getAll(): Collection
    {
        return Cache::remember($this->cacheKey, 60, function () {
            return ActivityRule::query()
                ->orderBy('id', 'desc')
                ->get();
        });
    }

    public function create(array $data): ActivityRule
    {
        $activityRule = ActivityRule::query()->create($data);

        Cache::forget($this->cacheKey);

        return $activityRule;
    }

    public function update(ActivityRule $activityRule, array $data): ActivityRule
    {
        $activityRule->update($data);

        Cache::forget($this->cacheKey);

        return $activityRule->refresh();
    }

    public function delete(ActivityRule $activityRule): void
    {
        $activityRule->delete();

        Cache::forget($this->cacheKey);
    }

    public function findActiveByCode(string $activityCode): ?ActivityRule
    {
        return Cache::remember("activity_rule_{$activityCode}", 60, function () use ($activityCode) {
            return ActivityRule::query()
                ->where('activity_code', $activityCode)
                ->where('is_active', true)
                ->first();
        });
    }
}