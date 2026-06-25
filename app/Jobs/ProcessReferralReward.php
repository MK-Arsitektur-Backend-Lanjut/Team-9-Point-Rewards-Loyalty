<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\PointActivityLog;
use App\Repositories\Contracts\ReferralLogRepositoryInterface;
use App\Services\MembershipTierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessReferralReward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const REFERRER_BONUS_POINTS = 50;
    private const REFEREE_BONUS_POINTS = 25;

    public function __construct(
        public int $refereeId,
        public int $referrerId,
        public string $referralCode
    ) {}

    public function handle(
        ReferralLogRepositoryInterface $referralLogRepository,
        MembershipTierService $membershipTierService
    ): void {
        DB::transaction(function () use ($referralLogRepository, $membershipTierService) {
            // Gunakan lockForUpdate untuk mencegah race condition (Pessimistic Locking)
            $referrer = User::lockForUpdate()->find($this->referrerId);
            $referee = User::lockForUpdate()->find($this->refereeId);

            if (!$referrer || !$referee) {
                return; // User deleted
            }

            // Mencegah double apply
            if ($referralLogRepository->existsByReferee($this->refereeId)) {
                return;
            }

            $referrer->points += self::REFERRER_BONUS_POINTS;
            $referrer->save();

            $referee->referred_by_user_id = $referrer->id;
            $referee->points += self::REFEREE_BONUS_POINTS;
            $referee->save();

            $referralLogRepository->create([
                'referrer_user_id' => $referrer->id,
                'referee_user_id' => $referee->id,
                'referral_code' => $this->referralCode,
                'referrer_bonus_points' => self::REFERRER_BONUS_POINTS,
                'referee_bonus_points' => self::REFEREE_BONUS_POINTS,
                'rewarded_at' => now(),
            ]);

            PointActivityLog::query()->create([
                'user_id' => $referrer->id,
                'activity_code' => 'referral_bonus_referrer',
                'points_earned' => self::REFERRER_BONUS_POINTS,
                'meta' => ['referee_user_id' => $referee->id],
                'earned_at' => now(),
            ]);

            PointActivityLog::query()->create([
                'user_id' => $referee->id,
                'activity_code' => 'referral_bonus_referee',
                'points_earned' => self::REFEREE_BONUS_POINTS,
                'meta' => ['referrer_user_id' => $referrer->id],
                'earned_at' => now(),
            ]);

            $membershipTierService->recalculateUserTier($referrer);
            $membershipTierService->recalculateUserTier($referee);
        });
    }
}
