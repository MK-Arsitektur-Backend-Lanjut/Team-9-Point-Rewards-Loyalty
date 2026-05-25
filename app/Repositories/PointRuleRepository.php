<?php

namespace App\Repositories;

use App\Models\PointRule;
use App\Repositories\Contracts\PointRuleRepositoryContract;

class PointRuleRepository implements PointRuleRepositoryContract
{
    /**
     * Get all active rules
     */
    public function getActive(): array
    {
        return PointRule::where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Get rule by ID
     */
    public function getById(int $id): ?object
    {
        return PointRule::find($id);
    }

    /**
     * Get rule by name
     */
    public function getByName(string $name): ?object
    {
        return PointRule::where('rule_name', $name)->first();
    }

    /**
     * Create new rule
     */
    public function create(array $data): object
    {
        return PointRule::create($data);
    }

    /**
     * Update rule
     */
    public function update(int $id, array $data): bool
    {
        return PointRule::where('id', $id)->update($data) > 0;
    }

    /**
     * Get multiplier for tier
     */
    public function getMultiplierForTier(int $ruleId, string $tier): float
    {
        $rule = PointRule::find($ruleId);
        if (!$rule || !$rule->multiplier_rules) {
            return 1.0;
        }

        $multipliers = $rule->multiplier_rules;
        return $multipliers[$tier] ?? 1.0;
    }

    /**
     * Calculate final points with multiplier
     */
    public function calculatePoints(int $ruleId, string $tier): int
    {
        $rule = PointRule::find($ruleId);
        if (!$rule) {
            return 0;
        }

        $multiplier = $this->getMultiplierForTier($ruleId, $tier);
        return (int) ($rule->base_points * $multiplier);
    }
}
