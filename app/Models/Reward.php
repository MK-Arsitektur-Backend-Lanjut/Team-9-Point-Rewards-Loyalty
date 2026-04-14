<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'points_required',
        'stock',
        'is_physical',
        'is_active',
    ];

    protected $casts = [
        'is_physical' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
}
