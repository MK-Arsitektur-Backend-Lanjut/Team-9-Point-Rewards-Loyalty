<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'points_balance',
        'points',
        'membership_tier_id',
        'referral_code',
        'referred_by_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function pointActivityLogs(): HasMany
    {
        return $this->hasMany(PointActivityLog::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getActivePointsAttribute()
    {
        return $this->pointActivityLogs()
            ->where('point_status', 'active')
            ->where('expired_at', '>', now())
            ->sum('points_earned');
    }

    public function getPointsExpiringSoonAttribute()
    {
        return $this->pointActivityLogs()
            ->where('point_status', 'active')
            ->where('expired_at', '<=', now()->addDays(30))
            ->where('expired_at', '>', now())
            ->sum('points_earned');
    }

    public function getTotalPointsEarnedAttribute()
    {
        return $this->pointActivityLogs()
            ->where('points_earned', '>', 0)
            ->sum('points_earned');
    }

    #MODUL4

    public function membershipTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referees(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }
    #END MODUL 4
}
