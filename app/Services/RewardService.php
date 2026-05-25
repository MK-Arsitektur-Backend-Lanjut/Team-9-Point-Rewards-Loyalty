<?php

namespace App\Services;

use App\Models\Reward;
use App\Repositories\Contracts\RewardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class RewardService
{
    public function __construct(
        private readonly RewardRepositoryInterface $rewardRepository
    ) {
    }

    public function list(): Collection
    {
        return $this->rewardRepository->getAll();
    }

    public function create(array $data): Reward
    {
        return $this->rewardRepository->create($data);
    }

    public function update(Reward $reward, array $data): Reward
    {
        return $this->rewardRepository->update($reward, $data);
    }

    public function delete(Reward $reward): void
    {
        $this->rewardRepository->delete($reward);
    }

    public function reduceStock(Reward $reward, int $quantity): void
    {
        $isUpdated = $this->rewardRepository->decrementStock($reward, $quantity);
        if (! $isUpdated) {
            throw ValidationException::withMessages([
                'stock' => 'Stok hadiah tidak cukup untuk diproses.',
            ]);
        }
    }
}
