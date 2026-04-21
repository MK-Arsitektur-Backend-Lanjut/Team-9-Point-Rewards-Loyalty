<?php

namespace App\Services;

use App\Models\MembershipTier;
use App\Models\User;
use App\Repositories\Contracts\MembershipTierRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MembershipTierService
{
    public function __construct(
        private readonly MembershipTierRepositoryInterface $membershipTierRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function listTiers(): Collection
    {
        return $this->membershipTierRepository->getAllOrdered();
    }

    public function createTier(array $payload): MembershipTier
    {
        return $this->membershipTierRepository->create($payload);
    }

    public function updateTier(MembershipTier $membershipTier, array $payload): MembershipTier
    {
        return $this->membershipTierRepository->update($membershipTier, $payload);
    }

    public function deleteTier(MembershipTier $membershipTier): void
    {
        $this->membershipTierRepository->delete($membershipTier);
    }

    public function recalculateUserTierByUserId(int $userId): array
    {
        $user = $this->userRepository->findOrFail($userId);

        return $this->recalculateUserTier($user);
    }

    public function recalculateUserTier(User $user): array
    {
        $resolvedTier = $this->membershipTierRepository->resolveTierByPoints((int) $user->points);

        $this->userRepository->update($user, [
            'membership_tier_id' => $resolvedTier?->id,
        ]);

        return [
            'user_id' => $user->id,
            'points' => (int) $user->points,
            'tier' => $resolvedTier,
        ];
    }

    public function resolveMultiplier(User $user): float
    {
        if (! $user->relationLoaded('membershipTier')) {
            $user->load('membershipTier');
        }

        return (float) ($user->membershipTier?->point_multiplier ?? 1.0);
    }
}
