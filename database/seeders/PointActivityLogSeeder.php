<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PointActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan query log
        DB::disableQueryLog();
        
        // Gunakan DB facade langsung untuk operasi massal
        $now = Carbon::now();
        
        // Optimasi: ambil user IDs sekali saja
        $users = DB::table('users')->pluck('id')->toArray();
        
        if (empty($users)) {
            // Buat user jika belum ada
            User::factory(200)->create();
            $users = DB::table('users')->pluck('id')->toArray();
        }
        
        $activityCodes = ['TRX_PURCHASE', 'DAILY_LOGIN', 'REVIEW_PRODUCT', 'PROFILE_COMPLETED'];
        $targetRows = 35000;
        
        // Gunakan DB facade untuk count (lebih ringan)
        $existingRows = DB::table('point_activity_logs')->count();
        $remainingRows = max(0, $targetRows - $existingRows);
        
        if ($remainingRows === 0) {
            return;
        }
        
        $batchSize = 2000; // Bisa dinaikkan ke 5000 setelah testing
        $rows = [];
        $totalInserted = 0;
        
        // Pre-calc untuk performa
        $startTime = microtime(true);
        
        for ($i = 1; $i <= $remainingRows; $i++) {
            // Optimasi: hitung di luar loop sebisa mungkin
            $minutesAgo = mt_rand(1, 60000);
            $earnedAt = $now->copy()->subMinutes($minutesAgo);
            
            $rows[] = [
                'user_id' => $users[array_rand($users)],
                'activity_code' => $activityCodes[array_rand($activityCodes)],
                'points_earned' => mt_rand(2, 30),
                'meta' => json_encode(['source' => 'seeder', 'batch' => ceil($i / 1000)]),
                'earned_at' => $earnedAt,
                'expired_at' => $earnedAt->copy()->addYear(),
                'point_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            // Insert per batch
            if (count($rows) >= $batchSize) {
                DB::table('point_activity_logs')->insert($rows);
                $totalInserted += count($rows);
                $this->command->info("Inserted {$totalInserted} / {$remainingRows} records");
                $rows = [];
                
                // Bebaskan memory
                gc_collect_cycles();
            }
        }
        
        // Insert sisa data
        if (!empty($rows)) {
            DB::table('point_activity_logs')->insert($rows);
            $totalInserted += count($rows);
        }
        
        $timeTaken = round(microtime(true) - $startTime, 2);
        $this->command->info("✓ Seeded {$totalInserted} records in {$timeTaken} seconds");
    }
}