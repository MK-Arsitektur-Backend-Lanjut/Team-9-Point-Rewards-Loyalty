<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralSeeder extends Seeder
{
    public function run(): void
    {
        $allUsers = User::pluck('id');
        $userCount = $allUsers->count();

        echo "Starting to create 10,000 referral records...\n";

        $target = 10000;
        $batchSize = 500;
        $inserted = 0;
        $batch = [];

        while ($inserted < $target) {
            $referrerId = $allUsers->random();
            $referredId = $allUsers->random();

            // Hindari self-referral
            if ($referrerId === $referredId) continue;

            $batch[] = [
                'referred_by_user_id' => $referrerId,
                'referred_user_id' => $referredId,
                'referral_code' => DB::table('users')->where('id', $referrerId)->value('referral_code'),
                'points_awarded' => rand(50, 500),
                'status' => 'active',
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                DB::table('referrals')->insertOrIgnore($batch);

                $inserted += count($batch);
                echo "Inserted approx {$inserted} referrals...\n";

                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('referrals')->insertOrIgnore($batch);
        }

        echo "ReferralSeeder completed!\n";
    }
}