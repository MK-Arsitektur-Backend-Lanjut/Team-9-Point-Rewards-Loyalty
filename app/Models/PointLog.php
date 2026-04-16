<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'point_rule_id',
        'points_amount',
        'transaction_type',
        'description',
        'reference_id',
        'metadata',
        'status',
        'created_at'
    ];

    protected $casts = [
        'points_amount' => 'integer',
        'metadata' => 'json',
        'created_at' => 'datetime'
    ];

    // Transaction types
    public const TRANSACTION_EARN = 'earn';
    public const TRANSACTION_REDEEM = 'redeem';
    public const TRANSACTION_EXPIRE = 'expire';
    public const TRANSACTION_REFERRAL = 'referral';
    public const TRANSACTION_ADJUSTMENT = 'adjustment';

    // Status
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the user that owns this log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the point rule
     */
    public function pointRule(): BelongsTo
    {
        return $this->belongsTo(PointRule::class);
    }
}
