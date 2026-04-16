<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'membership_tier',
        'referral_code',
        'referred_by_user_id',
        'point_multiplier'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the PointBalance for the user
     */
    public function pointBalance(): HasOne
    {
        return $this->hasOne(PointBalance::class);
    }

    /**
     * Get PointLog records for the user
     */
    public function pointLogs(): HasMany
    {
        return $this->hasMany(PointLog::class);
    }

    /**
     * Get referral records where this user is the referrer
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referred_by_user_id');
    }

    /**
     * Get the user who referred this user
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }
}
