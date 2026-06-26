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
        // 1. FAST-FAIL VALIDATION (Tanpa Lock)
        $referee = $this->userRepository->findOrFail($refereeUserId);

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

        if ($referrerCandidate->id === $referee->id) {
            throw ValidationException::withMessages([
                'referral_code' => 'User tidak bisa menggunakan kode referral miliknya sendiri.',
            ]);
        }

        // 2. DISPATCH BACKGROUND JOB
        \App\Jobs\ProcessReferralReward::dispatch(
            $referee->id,
            $referrerCandidate->id,
            $referralCode
        );

        // 3. RETURN RESPONSE ASYNC
        return [
            'message' => 'Referral code accepted. Points will be awarded shortly in the background.',
            'referrer' => [
                'user_id' => $referrerCandidate->id,
                'status' => 'processing',
            ],
            'referee' => [
                'user_id' => $referee->id,
                'status' => 'processing',
            ],
        ];
    }

    private function makeUniqueCode(int $userId): string
    {
        do {
            $code = sprintf('RF%d%s', $userId, strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
        } while ($this->userRepository->findByReferralCode($code));

        return $code;
    }
}
