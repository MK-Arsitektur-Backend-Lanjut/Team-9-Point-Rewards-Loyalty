<?php

namespace App\Services;

use App\Models\PointActivityLog;
use App\Models\Reward;
use App\Repositories\Contracts\RewardRedemptionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\RewardRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardRedemptionService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RewardRepositoryInterface $rewardRepository,
        private readonly RewardRedemptionRepositoryInterface $rewardRedemptionRepository,
        private readonly MembershipTierService $membershipTierService
    ) {
    }

    public function redeem(int $userId, Reward $reward, int $quantity): array
    {
        if (! $reward->is_active) {
            throw ValidationException::withMessages([
                'reward_id' => 'Reward tidak aktif.',
            ]);
        }

        return DB::transaction(function () use ($userId, $reward, $quantity) {
            $user = $this->userRepository->findOrFailWithLock($userId);
            $pointsSpent = (int) $reward->points_required * $quantity;

            if ((int) $user->points < $pointsSpent) {
                throw ValidationException::withMessages([
                    'points' => 'Poin user tidak cukup untuk redeem reward ini.',
                ]);
            }

            $stockReduced = $this->rewardRepository->decrementStock($reward, $quantity);
            if (! $stockReduced) {
                throw ValidationException::withMessages([
                    'stock' => 'Stok reward tidak mencukupi.',
                ]);
            }

            $user->points -= $pointsSpent;
            $user->save();

            $status = $reward->is_physical ? 'pending' : 'approved';

            $redemption = $this->rewardRedemptionRepository->create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'quantity' => $quantity,
                'points_spent' => $pointsSpent,
                'status' => $status,
            ]);

            PointActivityLog::query()->create([
                'user_id' => $user->id,
                'activity_code' => 'reward_redeem',
                'points_earned' => 0,
                'meta' => [
                    'reward_id' => $reward->id,
                    'quantity' => $quantity,
                    'points_spent' => $pointsSpent,
                ],
                'earned_at' => now(),
            ]);

            $tierResult = $this->membershipTierService->recalculateUserTier($user);

            return [
                'message' => 'Redeem reward berhasil diproses.',
                'redemption' => $redemption,
                'points_spent' => $pointsSpent,
                'remaining_points' => (int) $user->points,
                'tier' => $tierResult['tier'],
            ];
        });
    }
}
