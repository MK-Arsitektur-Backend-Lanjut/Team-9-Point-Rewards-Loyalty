<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait PointActivityLogScopes
{
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('point_status', 'active')
                     ->where('expired_at', '>', now());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->active()
                     ->where('expired_at', '<=', now()->addDays($days))
                     ->where('expired_at', '>', now());
    }
}
