<?php

namespace App\Repositories\Eloquent;

use App\Models\RewardRedemption;
use App\Repositories\Contracts\RewardRedemptionRepositoryInterface;

class RewardRedemptionRepository implements RewardRedemptionRepositoryInterface
{
    public function create(array $data): RewardRedemption
    {
        return RewardRedemption::query()->create($data);
    }
}
