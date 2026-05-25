<?php

namespace App\Services;

use App\Models\PointActivityLog;
use App\Repositories\Contracts\ReferralLogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    private const REFERRER_BONUS_POINTS = 50;
    private const REFEREE_BONUS_POINTS = 25;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ReferralLogRepositoryInterface $referralLogRepository,
        private readonly MembershipTierService $membershipTierService
    ) {
    }

    public function generateReferralCode(int $userId): array
    {
        $user = $this->userRepository->findOrFail($userId);

        if ($user->referral_code) {
            return [
                'user_id' => $user->id,
                'referral_code' => $user->referral_code,
            ];
        }

        $code = $this->makeUniqueCode($user->id);
        $this->userRepository->update($user, ['referral_code' => $code]);

        return [
            'user_id' => $user->id,
            'referral_code' => $code,
        ];
    }

    public function applyReferral(int $refereeUserId, string $referralCode): array
    {
        return DB::transaction(function () use ($refereeUserId, $referralCode) {
            $referee = $this->userRepository->findOrFailWithLock($refereeUserId);

            if ($referee->referred_by_user_id) {
                throw ValidationException::withMessages([
                    'referral_code' => 'User sudah pernah menggunakan referral.',
                ]);
            }

            if ($this->referralLogRepository->existsByReferee($referee->id)) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Referral untuk user ini sudah tercatat.',
                ]);
            }

            $referrerCandidate = $this->userRepository->findByReferralCode($referralCode);

            if (! $referrerCandidate) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Kode referral tidak valid.',
                ]);
            }

            $referrer = $this->userRepository->findOrFailWithLock($referrerCandidate->id);

            if ($referrer->id === $referee->id) {
                throw ValidationException::withMessages([
                    'referral_code' => 'User tidak bisa menggunakan kode referral miliknya sendiri.',
                ]);
            }

            $referrer->points += self::REFERRER_BONUS_POINTS;
            $referrer->save();

            $referee->referred_by_user_id = $referrer->id;
            $referee->points += self::REFEREE_BONUS_POINTS;
            $referee->save();

            $this->referralLogRepository->create([
                'referrer_user_id' => $referrer->id,
                'referee_user_id' => $referee->id,
                'referral_code' => $referralCode,
                'referrer_bonus_points' => self::REFERRER_BONUS_POINTS,
                'referee_bonus_points' => self::REFEREE_BONUS_POINTS,
                'rewarded_at' => now(),
            ]);

            PointActivityLog::query()->create([
                'user_id' => $referrer->id,
                'activity_code' => 'referral_bonus_referrer',
                'points_earned' => self::REFERRER_BONUS_POINTS,
                'meta' => [
                    'referee_user_id' => $referee->id,
                ],
                'earned_at' => now(),
            ]);

            PointActivityLog::query()->create([
                'user_id' => $referee->id,
                'activity_code' => 'referral_bonus_referee',
                'points_earned' => self::REFEREE_BONUS_POINTS,
                'meta' => [
                    'referrer_user_id' => $referrer->id,
                ],
                'earned_at' => now(),
            ]);

            $referrerTier = $this->membershipTierService->recalculateUserTier($referrer);
            $refereeTier = $this->membershipTierService->recalculateUserTier($referee);

            return [
                'message' => 'Referral berhasil diterapkan.',
                'referrer' => [
                    'user_id' => $referrer->id,
                    'bonus_points' => self::REFERRER_BONUS_POINTS,
                    'total_points' => (int) $referrer->points,
                    'tier' => $referrerTier['tier'],
                ],
                'referee' => [
                    'user_id' => $referee->id,
                    'bonus_points' => self::REFEREE_BONUS_POINTS,
                    'total_points' => (int) $referee->points,
                    'tier' => $refereeTier['tier'],
                ],
            ];
        });
    }

    private function makeUniqueCode(int $userId): string
    {
        do {
            $code = sprintf('RF%d%s', $userId, strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
        } while ($this->userRepository->findByReferralCode($code));

        return $code;
    }
}
