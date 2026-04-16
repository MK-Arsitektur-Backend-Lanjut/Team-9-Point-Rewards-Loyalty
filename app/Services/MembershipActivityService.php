<?php

namespace App\Services;

use App\Models\PointActivityLog;
use App\Repositories\Contracts\ActivityRuleReadRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipActivityService
{
    public function __construct(
        private readonly ActivityRuleReadRepositoryInterface $activityRuleReadRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MembershipTierService $membershipTierService
    ) {
    }

    public function triggerWithMultiplier(int $userId, string $activityCode): array
    {
        return DB::transaction(function () use ($userId, $activityCode) {
            $user = $this->userRepository->findOrFailWithLock($userId);
            $rule = $this->activityRuleReadRepository->findActiveByCode($activityCode);

            if (! $rule) {
                throw ValidationException::withMessages([
                    'activity_code' => 'Activity rule tidak ditemukan atau tidak aktif.',
                ]);
            }

            if ($rule->starts_at && Carbon::now()->lt($rule->starts_at)) {
                throw ValidationException::withMessages([
                    'activity_code' => 'Rule belum aktif.',
                ]);
            }

            if ($rule->ends_at && Carbon::now()->gt($rule->ends_at)) {
                throw ValidationException::withMessages([
                    'activity_code' => 'Rule sudah expired.',
                ]);
            }

            $multiplier = $this->membershipTierService->resolveMultiplier($user);
            $basePoints = (int) $rule->point_value;
            $pointsAdded = (int) floor($basePoints * $multiplier);

            $user->points += $pointsAdded;
            $user->save();

            PointActivityLog::query()->create([
                'user_id' => $user->id,
                'activity_code' => $activityCode,
                'points_earned' => $pointsAdded,
                'meta' => [
                    'rule_name' => $rule->name,
                    'base_points' => $basePoints,
                    'multiplier' => $multiplier,
                ],
                'earned_at' => now(),
            ]);

            $tierResult = $this->membershipTierService->recalculateUserTier($user);

            return [
                'message' => 'Poin berhasil ditambahkan dengan multiplier tier.',
                'activity_code' => $activityCode,
                'base_points' => $basePoints,
                'multiplier' => $multiplier,
                'points_added' => $pointsAdded,
                'total_points' => (int) $user->points,
                'tier' => $tierResult['tier'],
            ];
        });
    }
}
