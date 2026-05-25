<?php

namespace App\Repositories\Eloquent;

use App\Models\MembershipTier;
use App\Repositories\Contracts\MembershipTierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class MembershipTierRepository implements MembershipTierRepositoryInterface
{
    protected string $cacheKey = 'membership_tiers.all';

    public function getAllOrdered(): Collection
    {
        return Cache::remember($this->cacheKey, 60, function () {
            return MembershipTier::query()
                ->orderBy('min_points')
                ->get();
        });
    }

    public function create(array $data): MembershipTier
    {
        $membershipTier = MembershipTier::query()->create($data);

        Cache::forget($this->cacheKey);

        return $membershipTier;
    }

    public function update(MembershipTier $membershipTier, array $data): MembershipTier
    {
        $membershipTier->update($data);

        Cache::forget($this->cacheKey);

        return $membershipTier->refresh();
    }

    public function delete(MembershipTier $membershipTier): void
    {
        $membershipTier->delete();

        Cache::forget($this->cacheKey);
    }

    public function resolveTierByPoints(int $points): ?MembershipTier
    {
        return Cache::remember("membership_tier_{$points}", 60, function () use ($points) {
            return MembershipTier::query()
                ->where('is_active', true)
                ->where('min_points', '<=', $points)
                ->where(function ($query) use ($points) {
                    $query->whereNull('max_points')
                        ->orWhere('max_points', '>=', $points);
                })
                ->orderByDesc('min_points')
                ->first();
        });
    }
}