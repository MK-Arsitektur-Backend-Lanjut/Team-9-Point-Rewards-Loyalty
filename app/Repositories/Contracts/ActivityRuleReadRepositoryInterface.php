<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityRule;

interface ActivityRuleReadRepositoryInterface
{
    public function findActiveByCode(string $activityCode): ?ActivityRule;
}
