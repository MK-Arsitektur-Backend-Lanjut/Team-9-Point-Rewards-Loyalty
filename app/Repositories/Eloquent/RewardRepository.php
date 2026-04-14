<?php

namespace App\Repositories\Eloquent;

use App\Models\Reward;
use App\Repositories\Contracts\RewardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
        return Reward::query()
            ->whereKey($reward->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity) > 0;
    }
}
