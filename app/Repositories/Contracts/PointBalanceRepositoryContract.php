<?php

namespace App\Repositories\Contracts;

interface PointBalanceRepositoryContract
{
    /**
     * Get point balance for user
     */
    public function getByUserId(int $userId): ?object;

    /**
     * Get point balance with lock
     */
    public function getByUserIdWithLock(int $userId): ?object;

    /**
     * Create new point balance
     */
    public function create(array $data): object;

    /**
     * Update point balance
     */
    public function update(int $userId, array $data): bool;

    /**
     * Increment current balance
     */
    public function incrementBalance(int $userId, int $amount): bool;

    /**
     * Decrement current balance
     */
    public function decrementBalance(int $userId, int $amount): bool;

    /**
     * Add locked points
     */
    public function addLockedPoints(int $userId, int $amount): bool;

    /**
     * Release locked points
     */
    public function releaseLockedPoints(int $userId, int $amount): bool;

    /**
     * Get current balance amount
     */
    public function getCurrentBalance(int $userId): int;

    /**
     * Check if user has sufficient points for redemption
     */
    public function hasSufficientPoints(int $userId, int $requiredPoints): bool;
}
