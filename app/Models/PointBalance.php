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
        'current_points'
    ];

    protected $casts = [
        'current_points' => 'integer'
    ];

    /**
     * Relasi ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
