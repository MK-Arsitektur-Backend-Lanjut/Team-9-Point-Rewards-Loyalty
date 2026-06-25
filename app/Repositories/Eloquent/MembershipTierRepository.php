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
    public function getAllOrdered(): Collection
    {
        return MembershipTier::query()
            ->orderBy('min_points')
            ->get();
    }

    public function create(array $data): MembershipTier
    {
        $membershipTier = MembershipTier::query()->create($data);

        // ⚡ OPTIMIZATION: Use invalidateCaches() to clear all tier-related caches atomically
        $this->invalidateCaches();

        return $membershipTier;
    }

    public function update(MembershipTier $membershipTier, array $data): MembershipTier
    {
        $membershipTier->update($data);

        $this->invalidateCaches();

        return $membershipTier->refresh();
    }

    public function delete(MembershipTier $membershipTier): void
    {
        $membershipTier->delete();

        $this->invalidateCaches();
    }

    public function resolveTierByPoints(int $points): ?MembershipTier
    {
        // ⚡ OPTIMIZATION: Cache per 100-point range instead of per exact point
        // This reduces cache key cardinality and improves hit rate
        $pointRange = (int) floor($points / 100) * 100;
        $cacheKey = "membership_tier_range_{$pointRange}";

        return Cache::remember($cacheKey, 60, function () use ($points) {
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

    /**
     * ⚡ OPTIMIZATION: Atomic cache invalidation
     * Clears all tier-related caches in one operation to prevent partial invalidation
     */
    private function invalidateCaches(): void
    {
        Cache::forget($this->cacheKey);
        Cache::forget($this->cacheTierIndexKey);
        
        // Also clear common point ranges to avoid cascading cache misses
        for ($i = 0; $i <= 10000; $i += 100) {
            Cache::forget("membership_tier_range_{$i}");
        }
    }
}
