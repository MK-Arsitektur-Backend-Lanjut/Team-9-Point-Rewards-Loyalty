<?php

namespace App\Repositories;

use App\Models\Referral;
use App\Repositories\Contracts\ReferralRepositoryContract;

class ReferralRepository implements ReferralRepositoryContract
{
    /**
     * Create referral record
     */
    public function create(array $data): object
    {
        return Referral::create($data);
    }

    /**
     * Get referrals by user
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return Referral::where('referred_by_user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    /**
     * Get referral by code
     */
    public function getByCode(string $code): ?object
    {
        return Referral::where('referral_code', $code)->first();
    }

    /**
     * Check if referral exists
     */
    public function exists(int $referredBy, int $referredUser): bool
    {
        return Referral::where('referred_by_user_id', $referredBy)
            ->where('referred_user_id', $referredUser)
            ->exists();
    }

    /**
     * Update referral status
     */
    public function updateStatus(int $id, string $status): bool
    {
        return Referral::where('id', $id)->update(['status' => $status]) > 0;
    }

    /**
     * Get count of referrals by user
     */
    public function countByUser(int $userId): int
    {
        return Referral::where('referred_by_user_id', $userId)->count();
    }

    /**
     * Get total points from referrals
     */
    public function getTotalPointsFromReferrals(int $userId): int
    {
        return (int) Referral::where('referred_by_user_id', $userId)
            ->sum('points_awarded');
    }
}
