<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PointLog;
use App\Models\PointRule;
use App\Models\PointBalance;

class PointLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates 35,000+ point activity logs
     */
    public function run(): void
    {
        $users = User::all();
        $rules = PointRule::all();
        $transactionTypes = [
            PointLog::TRANSACTION_EARN,
            PointLog::TRANSACTION_REDEEM,
            PointLog::TRANSACTION_REFERRAL,
        ];

        echo "Starting to create 35,000 point logs...\n";

        $logCount = 0;
        $batchSize = 1000;
        $logs = [];

        for ($i = 0; $i < 35000; $i++) {
            $user = $users->random();
            $rule = $rules->random();
            $type = $transactionTypes[array_rand($transactionTypes)];

            // Determine points based on type and tier
            if ($type === PointLog::TRANSACTION_EARN) {
                $multipliers = ['bronze' => 1.0, 'silver' => 1.2, 'gold' => 1.5, 'platinum' => 2.0];
                $basePoints = $rule->base_points;
                $points = (int)($basePoints * $multipliers[$user->membership_tier] * $user->point_multiplier);
            } elseif ($type === PointLog::TRANSACTION_REDEEM) {
                $points = -rand(50, 500);
            } else {
                $points = rand(50, 300);
            }

            $logs[] = [
                'user_id' => $user->id,
                'point_rule_id' => $rule->id,
                'points_amount' => $points,
                'transaction_type' => $type,
                'description' => $rule->description,
                'reference_id' => uniqid('log_'),
                'metadata' => json_encode(['source' => 'seeder']),
                'status' => PointLog::STATUS_COMPLETED,
                'created_at' => now()->subDays(rand(0, 365))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                'updated_at' => now(),
            ];

            $logCount++;

            // Batch insert
            if (count($logs) >= $batchSize) {
                PointLog::insert($logs);
                echo "Inserted {$logCount} point logs...\n";
                $logs = [];
            }
        }

        // Insert remaining
        if (!empty($logs)) {
            PointLog::insert($logs);
        }

        // Update user point balances based on logs
        echo "Updating user point balances...\n";
        
        $batchSize = 50;
        $counter = 0;
        foreach ($users->chunk($batchSize) as $chunk) {
            foreach ($chunk as $user) {
                $totalEarned = PointLog::where('user_id', $user->id)
                    ->where('transaction_type', PointLog::TRANSACTION_EARN)
                    ->where('status', PointLog::STATUS_COMPLETED)
                    ->sum('points_amount');

                $totalRedeemed = PointLog::where('user_id', $user->id)
                    ->where('transaction_type', PointLog::TRANSACTION_REDEEM)
                    ->where('status', PointLog::STATUS_COMPLETED)
                    ->sum('points_amount');

                $currentBalance = max(0, $totalEarned + $totalRedeemed);

                PointBalance::where('user_id', $user->id)->update([
                    'current_balance' => $currentBalance,
                    'lifetime_points' => $totalEarned,
                ]);

                $counter++;
                if ($counter % 100 === 0) {
                    echo "Updated {$counter} user balances...\n";
                }
            }
        }

        echo "PointLogSeeder completed! Total logs: {$logCount}\n";
    }
}
