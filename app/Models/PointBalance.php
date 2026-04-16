<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_balance',
        'expired_points',
        'locked_points',
        'lifetime_points'
    ];

    protected $casts = [
        'current_balance' => 'integer',
        'expired_points' => 'integer',
        'locked_points' => 'integer',
        'lifetime_points' => 'integer'
    ];

    public $timestamps = true;

    /**
     * Get the user that owns this balance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
