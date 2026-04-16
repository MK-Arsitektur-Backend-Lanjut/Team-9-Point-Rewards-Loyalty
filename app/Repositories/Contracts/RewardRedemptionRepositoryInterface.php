<?php

namespace App\Repositories\Contracts;

use App\Models\RewardRedemption;

interface RewardRedemptionRepositoryInterface
{
    public function create(array $data): RewardRedemption;
}
