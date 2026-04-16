<?php

namespace App\Repositories\Contracts;

interface PointRuleRepositoryContract
{
    /**
     * Get all active rules
     */
    public function getActive(): array;

    /**
     * Get rule by ID
     */
    public function getById(int $id): ?object;

    /**
     * Get rule by name
     */
    public function getByName(string $name): ?object;

    /**
     * Create new rule
     */
    public function create(array $data): object;

    /**
     * Update rule
     */
    public function update(int $id, array $data): bool;

    /**
     * Get multiplier for tier
     */
    public function getMultiplierForTier(int $ruleId, string $tier): float;

    /**
     * Calculate final points with multiplier
     */
    public function calculatePoints(int $ruleId, string $tier): int;
}
