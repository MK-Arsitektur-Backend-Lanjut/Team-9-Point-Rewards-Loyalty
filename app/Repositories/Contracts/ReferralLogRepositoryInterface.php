<?php

namespace App\Repositories\Contracts;

use App\Models\ReferralLog;

interface ReferralLogRepositoryInterface
{
    public function create(array $data): ReferralLog;

    public function existsByReferee(int $refereeUserId): bool;
}
