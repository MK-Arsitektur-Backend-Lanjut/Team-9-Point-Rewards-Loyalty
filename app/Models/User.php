<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'membership_tier',
        'points_balance',
        'points',
        'membership_tier_id',
        'referral_code',
        'referred_by_user_id',
        'point_multiplier',
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
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * Get points balance attribute
     */
    public function getPointsBalanceAttribute($value)
    {
        if (isset($this->attributes['points']) && $this->attributes['points'] !== null) {
            return (int) $this->attributes['points'];
        }

        return (int) $value;
    }

    /**
     * Get reward redemptions for the user
     */
    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    /**
     * Get point activity logs for the user
     */
    public function pointActivityLogs(): HasMany
    {
        return $this->hasMany(PointActivityLog::class);
    }

    /**
     * Get JWT identifier
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get JWT custom claims
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get active points attribute (not expired)
     */
    public function getActivePointsAttribute()
    {
        return $this->pointActivityLogs()
            ->where('point_status', 'active')
            ->where('expired_at', '>', now())
            ->sum('points_earned');
    }

    /**
     * Get points expiring soon attribute (within 30 days)
     */
    public function getPointsExpiringSoonAttribute()
    {
        return $this->pointActivityLogs()
            ->where('point_status', 'active')
            ->where('expired_at', '<=', now()->addDays(30))
            ->where('expired_at', '>', now())
            ->sum('points_earned');
    }

    /**
     * Get total points earned attribute
     */
    public function getTotalPointsEarnedAttribute()
    {
        return $this->pointActivityLogs()
            ->where('points_earned', '>', 0)
            ->sum('points_earned');
    }

    /**
     * MODUL 4: Get membership tier relation
     */
    public function membershipTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class);
    }

    /**
     * MODUL 4: Get referrer (user who referred this user)
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * MODUL 4: Get referees (users referred by this user)
     */
    public function referees(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }
}