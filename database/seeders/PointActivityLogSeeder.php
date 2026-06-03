<?php

namespace Database\Seeders;

use App\Models\PointActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PointActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $users = User::query()->pluck('id')->all();
        if (empty($users)) {
            User::factory(200)->create();
            $users = User::query()->pluck('id')->all();
        }

        $activityCodes = ['TRX_PURCHASE', 'DAILY_LOGIN', 'REVIEW_PRODUCT', 'PROFILE_COMPLETED'];
        $now = now();
        $rows = [];
        $targetRows = 35000;
        $existingRows = PointActivityLog::query()->count();
        $remainingRows = max(0, $targetRows - $existingRows);
        $batchSize = 2000;

        if ($remainingRows === 0) {
            return;
        }

        for ($i = 1; $i <= $remainingRows; $i++) {
            $earnedAt = Carbon::now()->subMinutes(mt_rand(1, 60000));
            $pointsEarned = mt_rand(2, 30);
            $rows[] = [
                'user_id' => $users[array_rand($users)],
                'activity_code' => $activityCodes[array_rand($activityCodes)],
                'points_earned' => $pointsEarned,
                'meta' => json_encode(['source' => 'seeder', 'batch' => ceil($i / 1000)]),
                'earned_at' => $earnedAt,
                'expired_at' => $earnedAt->copy()->addYear(),
                'point_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === $batchSize) {
                PointActivityLog::query()->insert($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            PointActivityLog::query()->insert($rows);
        }
    }
}
