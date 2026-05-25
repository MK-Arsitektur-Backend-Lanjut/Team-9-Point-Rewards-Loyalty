<?php

namespace App\Services;

use App\Models\PointBalance;
use App\Models\PointLog;
use App\Models\User;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\RaceConditionException;
use App\Exceptions\InvalidPointRuleException;
use App\Repositories\Contracts\PointBalanceRepositoryContract;
use App\Repositories\Contracts\PointLogRepositoryContract;
use App\Repositories\Contracts\PointRuleRepositoryContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Core Reward Processing Service
 * 
 * Handles:
 * - Automatic point adding with multipliers
 * - Point validation (sufficient balance)
 * - Race condition prevention (pessimistic locking)
 */
class RewardProcessingService
{
    public function __construct(
        private PointBalanceRepositoryContract $pointBalanceRepo,
        private PointLogRepositoryContract $pointLogRepo,
        private PointRuleRepositoryContract $pointRuleRepo
    ) {}

    /**
     * Add points automatically with race condition handling
     * 
     * @param int $userId
     * @param int $pointRuleId
     * @param array $metadata
     * @return array
     * @throws InvalidPointRuleException
     * @throws RaceConditionException
     */
    public function addPointsAutomatic(
        int $userId,
        int $pointRuleId,
        array $metadata = []
    ): array {
        return DB::transaction(function () use ($userId, $pointRuleId, $metadata) {
            try {
                // Validate user exists
                $user = User::findOrFail($userId);

                // Validate point rule exists and is active
                $rule = $this->pointRuleRepo->getById($pointRuleId);
                if (!$rule || !$rule->is_active) {
                    throw new InvalidPointRuleException("Point rule not found or inactive");
                }

                // Get point balance with pessimistic lock
                $balance = $this->pointBalanceRepo->getByUserIdWithLock($userId);

                if (!$balance) {
                    // Create balance if not exists
                    $balance = $this->pointBalanceRepo->create([
                        'user_id' => $userId,
                        'current_balance' => 0,
                        'expired_points' => 0,
                        'locked_points' => 0,
                        'lifetime_points' => 0,
                    ]);
                }

                // Calculate points with multiplier based on user tier
                $pointsToAdd = $this->pointRuleRepo->calculatePoints(
                    $pointRuleId,
                    $user->membership_tier
                );

                // Add user point multiplier
                $pointsToAdd = (int) ($pointsToAdd * $user->point_multiplier);

                // Update balance
                $this->pointBalanceRepo->incrementBalance($userId, $pointsToAdd);
                $this->pointBalanceRepo->update($userId, [
                    'lifetime_points' => DB::raw('lifetime_points + ' . $pointsToAdd)
                ]);

                // Create log entry
                $log = $this->pointLogRepo->create([
                    'user_id' => $userId,
                    'point_rule_id' => $pointRuleId,
                    'points_amount' => $pointsToAdd,
                    'transaction_type' => PointLog::TRANSACTION_EARN,
                    'description' => $rule->description,
                    'reference_id' => uniqid('earn_'),
                    'metadata' => $metadata,
                    'status' => PointLog::STATUS_COMPLETED,
                    'created_at' => now()
                ]);

                Log::info('Points added successfully', [
                    'user_id' => $userId,
                    'points' => $pointsToAdd,
                    'log_id' => $log->id
                ]);

                return [
                    'success' => true,
                    'user_id' => $userId,
                    'points_added' => $pointsToAdd,
                    'new_balance' => $balance->current_balance + $pointsToAdd,
                    'log_id' => $log->id,
                    'message' => 'Points added successfully'
                ];

            } catch (\Exception $e) {
                Log::error('Failed to add points', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }, max_attempts: 3);
    }

    /**
     * Redeem points with validation
     * 
     * @param int $userId
     * @param int $pointsToRedeem
     * @param string $description
     * @param array $metadata
     * @return array
     * @throws InsufficientPointsException
     * @throws RaceConditionException
     */
    public function redeemPoints(
        int $userId,
        int $pointsToRedeem,
        string $description = "Point Redemption",
        array $metadata = []
    ): array {
        return DB::transaction(function () use ($userId, $pointsToRedeem, $description, $metadata) {
            try {
                // Get balance with lock
                $balance = $this->pointBalanceRepo->getByUserIdWithLock($userId);

                if (!$balance) {
                    throw new InsufficientPointsException("User has no points balance");
                }

                // Check sufficient points
                $availablePoints = $balance->current_balance - $balance->locked_points;
                if ($availablePoints < $pointsToRedeem) {
                    throw new InsufficientPointsException(
                        "Insufficient points. Available: {$availablePoints}, Required: {$pointsToRedeem}"
                    );
                }

                // Deduct points
                $this->pointBalanceRepo->decrementBalance($userId, $pointsToRedeem);

                // Create log entry
                $log = $this->pointLogRepo->create([
                    'user_id' => $userId,
                    'points_amount' => $pointsToRedeem,
                    'transaction_type' => PointLog::TRANSACTION_REDEEM,
                    'description' => $description,
                    'reference_id' => uniqid('redeem_'),
                    'metadata' => $metadata,
                    'status' => PointLog::STATUS_COMPLETED,
                    'created_at' => now()
                ]);

                Log::info('Points redeemed successfully', [
                    'user_id' => $userId,
                    'points' => $pointsToRedeem,
                    'log_id' => $log->id
                ]);

                return [
                    'success' => true,
                    'user_id' => $userId,
                    'points_redeemed' => $pointsToRedeem,
                    'new_balance' => $balance->current_balance - $pointsToRedeem,
                    'log_id' => $log->id,
                    'message' => 'Points redeemed successfully'
                ];

            } catch (InsufficientPointsException $e) {
                Log::warning('Redemption failed - insufficient points', [
                    'user_id' => $userId,
                    'requested' => $pointsToRedeem
                ]);
                throw $e;
            } catch (\Exception $e) {
                Log::error('Failed to redeem points', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }, max_attempts: 3);
    }

    /**
     * Validate point balance
     */
    public function validateBalance(int $userId, int $requiredPoints): bool
    {
        return $this->pointBalanceRepo->hasSufficientPoints($userId, $requiredPoints);
    }

    /**
     * Get user point balance info
     */
    public function getBalanceInfo(int $userId): ?array
    {
        $balance = $this->pointBalanceRepo->getByUserId($userId);
        if (!$balance) {
            return null;
        }

        return [
            'user_id' => $userId,
            'current_balance' => $balance->current_balance,
            'available_balance' => $balance->current_balance - $balance->locked_points,
            'locked_points' => $balance->locked_points,
            'expired_points' => $balance->expired_points,
            'lifetime_points' => $balance->lifetime_points,
        ];
    }

    /**
     * Lock points for processing (prevents concurrent redemption)
     */
    public function lockPoints(int $userId, int $pointsToLock): bool
    {
        return $this->pointBalanceRepo->addLockedPoints($userId, $pointsToLock);
    }

    /**
     * Release locked points after processing
     */
    public function releaseLockedPoints(int $userId, int $pointsToRelease): bool
    {
        return $this->pointBalanceRepo->releaseLockedPoints($userId, $pointsToRelease);
    }
}
