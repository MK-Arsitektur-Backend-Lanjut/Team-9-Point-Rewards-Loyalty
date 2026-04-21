<?php

namespace App\Repositories\Eloquent;

use App\Models\ReferralLog;
use App\Repositories\Contracts\ReferralLogRepositoryInterface;

class ReferralLogRepository implements ReferralLogRepositoryInterface
{
    public function create(array $data): ReferralLog
    {
        return ReferralLog::query()->create($data);
    }

    public function existsByReferee(int $refereeUserId): bool
    {
        return ReferralLog::query()->where('referee_user_id', $refereeUserId)->exists();
    }
}
