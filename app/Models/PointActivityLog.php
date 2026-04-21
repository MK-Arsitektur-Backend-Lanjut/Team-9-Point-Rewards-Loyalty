<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointActivityLog extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
