<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityRule;
use App\Repositories\Contracts\ActivityRuleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ActivityRuleRepository implements ActivityRuleRepositoryInterface
{
    public function getAll(): Collection
    {
        return ActivityRule::query()->orderBy('id', 'desc')->get();
    }

    public function create(array $data): ActivityRule
    {
        return ActivityRule::query()->create($data);
    }

    public function update(ActivityRule $activityRule, array $data): ActivityRule
    {
        $activityRule->update($data);

        return $activityRule->refresh();
    }

    public function delete(ActivityRule $activityRule): void
    {
        $activityRule->delete();
    }

    // 🔥 TAMBAHAN UNTUK BUSINESS LOGIC
    public function findActiveByCode(string $activityCode): ?ActivityRule
    {
        return ActivityRule::query()
            ->where('activity_code', $activityCode)
            ->where('is_active', true)
            ->first();
    }
}