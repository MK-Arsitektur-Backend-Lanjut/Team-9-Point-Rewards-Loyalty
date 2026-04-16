<?php

namespace App\Repositories\Eloquent;

use App\Models\MembershipTier;
use App\Repositories\Contracts\MembershipTierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MembershipTierRepository implements MembershipTierRepositoryInterface
{
    public function getAllOrdered(): Collection
    {
        return MembershipTier::query()
            ->orderBy('min_points')
            ->get();
    }

    public function create(array $data): MembershipTier
    {
        return MembershipTier::query()->create($data);
    }

    public function update(MembershipTier $membershipTier, array $data): MembershipTier
    {
        $membershipTier->update($data);

        return $membershipTier->refresh();
    }

    public function delete(MembershipTier $membershipTier): void
    {
        $membershipTier->delete();
    }

    public function resolveTierByPoints(int $points): ?MembershipTier
    {
        return MembershipTier::query()
            ->where('is_active', true)
            ->where('min_points', '<=', $points)
            ->where(function ($query) use ($points) {
                $query->whereNull('max_points')
                    ->orWhere('max_points', '>=', $points);
            })
            ->orderByDesc('min_points')
            ->first();
    }
}
