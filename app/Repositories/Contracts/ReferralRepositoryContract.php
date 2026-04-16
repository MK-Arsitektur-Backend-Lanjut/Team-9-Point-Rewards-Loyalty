<?php

namespace App\Repositories\Contracts;

interface ReferralRepositoryContract
{
    /**
     * Create referral record
     */
    public function create(array $data): object;

    /**
     * Get referrals by user
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array;

    /**
     * Get referral by code
     */
    public function getByCode(string $code): ?object;

    /**
     * Check if referral exists
     */
    public function exists(int $referredBy, int $referredUser): bool;

    /**
     * Update referral status
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Get count of referrals by user
     */
    public function countByUser(int $userId): int;

    /**
     * Get total points from referrals
     */
    public function getTotalPointsFromReferrals(int $userId): int;
}
