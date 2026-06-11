<?php

namespace Database\Seeders;

use App\Models\PointActivityLog;
use App\Models\ReferralLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReferralLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $targetLogs = 10000;
        $existingCount = ReferralLog::query()->count();
        $remaining = max(0, $targetLogs - $existingCount);

        if ($remaining === 0) {
            return;
        }

        $minimumUsers = 11000;
        $currentUsers = User::query()->count();

        if ($currentUsers < $minimumUsers) {
            User::factory($minimumUsers - $currentUsers)->create();
            $currentUsers = $minimumUsers;
        }

        $userIds = User::query()->orderBy('id')->pluck('id')->all();
        $referrerIds = array_slice($userIds, 0, 1000);
        $refereeIds = array_slice($userIds, 1000, 10000);

        if (count($refereeIds) < $remaining) {
            $refereeIds = array_slice($userIds, 1000);
        }

        $referrerCodes = User::query()
            ->whereIn('id', $referrerIds)
            ->pluck('referral_code', 'id')
            ->all();

        $referralLogs = [];
        $activityRows = [];
        $userPoints = [];
        $userReferredBy = [];
        $updatedReferrerCodes = [];
        $now = now();

        for ($i = 0; $i < $remaining; $i++) {
            $refereeId = $refereeIds[$i];
            $referrerId = $referrerIds[array_rand($referrerIds)];
            $referralCode = $referrerCodes[$referrerId] ?? sprintf('RF%06d', $referrerId);

            if (! isset($referrerCodes[$referrerId])) {
                $referrerCodes[$referrerId] = $referralCode;
                $updatedReferrerCodes[$referrerId] = $referralCode;
            }

            $referralLogs[] = [
                'referrer_user_id' => $referrerId,
                'referee_user_id' => $refereeId,
                'referral_code' => $referralCode,
                'referrer_bonus_points' => 50,
                'referee_bonus_points' => 25,
                'rewarded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $userReferredBy[$refereeId] = $referrerId;
            $userPoints[$referrerId] = ($userPoints[$referrerId] ?? 0) + 50;
            $userPoints[$refereeId] = ($userPoints[$refereeId] ?? 0) + 25;

            $activityRows[] = [
                'user_id' => $referrerId,
                'activity_code' => 'referral_bonus_referrer',
                'points_earned' => 50,
                'meta' => json_encode(['referee_user_id' => $refereeId]),
                'earned_at' => $now,
                'expired_at' => $now->copy()->addYear(),
                'point_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $activityRows[] = [
                'user_id' => $refereeId,
                'activity_code' => 'referral_bonus_referee',
                'points_earned' => 25,
                'meta' => json_encode(['referrer_user_id' => $referrerId]),
                'earned_at' => $now,
                'expired_at' => $now->copy()->addYear(),
                'point_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $batchSize = 2000;
        $chunks = array_chunk($referralLogs, $batchSize);
        foreach ($chunks as $chunk) {
            ReferralLog::query()->insert($chunk);
        }

        $activityChunks = array_chunk($activityRows, $batchSize);
        foreach ($activityChunks as $chunk) {
            PointActivityLog::query()->insert($chunk);
        }

        foreach ($updatedReferrerCodes as $userId => $code) {
            User::query()->where('id', $userId)->update(['referral_code' => $code]);
        }

        foreach ($userReferredBy as $userId => $referrerId) {
            User::query()->where('id', $userId)->update(['referred_by_user_id' => $referrerId]);
        }

        foreach ($userPoints as $userId => $pointsDelta) {
            User::query()->where('id', $userId)->increment('points', $pointsDelta);
        }
    }
}
