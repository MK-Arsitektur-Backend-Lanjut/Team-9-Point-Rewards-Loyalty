<?php

namespace App\Repositories;

use App\Models\PointBalance;
use App\Repositories\Contracts\PointBalanceRepositoryContract;
use Illuminate\Support\Facades\DB;

class PointBalanceRepository implements PointBalanceRepositoryContract
{
    /**
     * Get point balance for user
     */
    public function getByUserId(int $userId): ?object
    {
        return PointBalance::where('user_id', $userId)->first();
    }

    /**
     * Get point balance with pessimistic lock for race condition handling
     */
    public function getByUserIdWithLock(int $userId): ?object
    {
        return PointBalance::where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Create new point balance
     */
    public function create(array $data): object
    {
        return PointBalance::create($data);
    }

    /**
     * Update point balance
     */
    public function update(int $userId, array $data): bool
    {
        return PointBalance::where('user_id', $userId)->update($data) > 0;
    }

    /**
     * Increment current balance
     */
    public function incrementBalance(int $userId, int $amount): bool
    {
        return PointBalance::where('user_id', $userId)
            ->increment('current_balance', $amount) > 0;
    }

    /**
     * Decrement current balance
     */
    public function decrementBalance(int $userId, int $amount): bool
    {
        return PointBalance::where('user_id', $userId)
            ->decrement('current_balance', $amount) > 0;
    }

    /**
     * Add locked points
     */
    public function addLockedPoints(int $userId, int $amount): bool
    {
        return PointBalance::where('user_id', $userId)
            ->increment('locked_points', $amount) > 0;
    }

    /**
     * Release locked points
     */
    public function releaseLockedPoints(int $userId, int $amount): bool
    {
        return PointBalance::where('user_id', $userId)
            ->decrement('locked_points', $amount) > 0;
    }

    /**
     * Get current balance amount
     */
    public function getCurrentBalance(int $userId): int
    {
        $balance = PointBalance::where('user_id', $userId)->first();
        return $balance ? $balance->current_balance : 0;
    }

    /**
     * Check if user has sufficient points for redemption
     */
    public function hasSufficientPoints(int $userId, int $requiredPoints): bool
    {
        $balance = PointBalance::where('user_id', $userId)->first();
        if (!$balance) {
            return false;
        }
        // Available balance = current balance - locked points
        return $balance->current_balance - $balance->locked_points >= $requiredPoints;
    }
}
