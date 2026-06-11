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
        $userIds = User::pluck('id')->toArray();
        $ruleIds = PointRule::pluck('id')->toArray();
        $transactionTypes = [
            PointLog::TRANSACTION_EARN,
            PointLog::TRANSACTION_REDEEM,
            PointLog::TRANSACTION_REFERRAL,
        ];

        echo "Starting to create 5,000 point logs...\n";

        $logCount = 0;
        $batchSize = 500;
        $logs = [];

        for ($i = 0; $i < 5000; $i++) {
            $userId = $userIds[array_rand($userIds)];
            $ruleId = $ruleIds[array_rand($ruleIds)];
            $type = $transactionTypes[array_rand($transactionTypes)];

            // Determine points based on type
            if ($type === PointLog::TRANSACTION_EARN) {
                $points = rand(10, 100);
            } elseif ($type === PointLog::TRANSACTION_REDEEM) {
                $points = -rand(50, 500);
            } else {
                $points = rand(50, 300);
            }

            $logs[] = [
                'user_id' => $userId,
                'point_rule_id' => $ruleId,
                'points_amount' => $points,
                'transaction_type' => $type,
                'description' => 'Seeded transaction',
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
                unset($logs);
                gc_collect_cycles();
            }
        }

        // Insert remaining
        if (!empty($logs)) {
            PointLog::insert($logs);
        }

        // Simplified - skip balance updates for seeding (can be run manually)
        echo "PointLogSeeder completed! Total logs: {$logCount}\n";
    }
}
