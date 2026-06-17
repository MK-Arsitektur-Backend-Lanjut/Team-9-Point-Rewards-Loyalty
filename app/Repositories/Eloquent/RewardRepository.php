<?php

namespace App\Repositories\Eloquent;

use App\Models\Reward;
use App\Repositories\Contracts\RewardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RewardRepository implements RewardRepositoryInterface
{
    protected string $cacheKey = 'rewards.all';

    public function getAll(): Collection
    {
        return Cache::remember($this->cacheKey, 60, function () {
            return Reward::query()
                ->orderBy('id', 'desc')
                ->get();
        });
    }

    public function create(array $data): Reward
    {
        $reward = Reward::query()->create($data);

        Cache::forget($this->cacheKey);

        return $reward;
    }

    public function update(Reward $reward, array $data): Reward
    {
        $reward->update($data);

        Cache::forget($this->cacheKey);

        return $reward->refresh();
    }

    public function delete(Reward $reward): void
    {
        $reward->delete();

        Cache::forget($this->cacheKey);
    }

    public function decrementStock(Reward $reward, int $quantity): bool
    {
        return DB::transaction(function () use ($reward, $quantity) {
            $currentReward = Reward::query()
                ->whereKey($reward->id)
                ->lockForUpdate()
                ->first();
            
            if (!$currentReward || $currentReward->stock < $quantity) {
                return false;
            }
            
            Reward::query()
                ->whereKey($reward->id)
                ->decrement('stock', $quantity);
            
            Cache::forget($this->cacheKey);
            
            return true;
        });
    }
}