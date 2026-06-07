<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\PointActivityLogScopes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointActivityLog extends Model
{
    use HasFactory, PointActivityLogScopes;

    protected $fillable = [
        'user_id',
        'activity_code',
        'points_earned',
        'meta',
        'earned_at',
        'expired_at',
        'point_status',
    ];
    

    protected $casts = [
        'meta' => 'array',
        'earned_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PointActivityLog $log) {
            if (! $log->expired_at && $log->points_earned > 0) {
                $log->expired_at = $log->earned_at ? $log->earned_at->copy()->addYear() : now()->addYear();
            }

            if (! $log->point_status) {
                if ($log->points_earned > 0) {
                    $log->point_status = 'active';
                } else {
                    $log->point_status = 'redeemed';
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
