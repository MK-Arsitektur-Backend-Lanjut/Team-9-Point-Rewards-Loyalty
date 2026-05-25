<?php

namespace App\Repositories\Contracts;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Collection;

interface RewardRepositoryInterface
{
    public function getAll(): Collection;

    public function create(array $data): Reward;

    public function update(Reward $reward, array $data): Reward;

    public function delete(Reward $reward): void;

    public function decrementStock(Reward $reward, int $quantity): bool;
}
