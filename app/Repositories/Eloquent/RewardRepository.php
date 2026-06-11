<?php

namespace App\Repositories\Eloquent;

use App\Models\Reward;
use App\Repositories\Contracts\RewardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
<<<<<<< Updated upstream
=======
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
>>>>>>> Stashed changes

class RewardRepository implements RewardRepositoryInterface
{
    public function getAll(): Collection
    {
        return Reward::query()->orderBy('id', 'desc')->get();
    }

    public function create(array $data): Reward
    {
        return Reward::query()->create($data);
    }

    public function update(Reward $reward, array $data): Reward
    {
        $reward->update($data);

        return $reward->refresh();
    }

    public function delete(Reward $reward): void
    {
        $reward->delete();
    }

    public function decrementStock(Reward $reward, int $quantity): bool
    {
<<<<<<< Updated upstream
        return Reward::query()
            ->whereKey($reward->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity) > 0;
=======
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
>>>>>>> Stashed changes
    }
}
