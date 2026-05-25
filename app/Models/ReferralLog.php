<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_user_id',
        'referee_user_id',
        'referral_code',
        'referrer_bonus_points',
        'referee_bonus_points',
        'rewarded_at',
    ];

    protected $casts = [
        'rewarded_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }
}
