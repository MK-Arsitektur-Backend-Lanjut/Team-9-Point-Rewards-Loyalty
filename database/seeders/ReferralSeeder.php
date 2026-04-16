<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Referral;
use App\Models\PointBalance;

class ReferralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates 10,000+ referral records
     */
    public function run(): void
    {
        $allUsers = User::all();
        $userCount = $allUsers->count();
        
        echo "Starting to create 10,000 referral records...\n";

        $referralCount = 0;
        $batchSize = 500;
        $referrals = [];

        for ($i = 0; $i < 10000; $i++) {
            $referrer = $allUsers->random();
            $referred = $allUsers->random();

            // Avoid self-referral
            while ($referrer->id === $referred->id) {
                $referred = $allUsers->random();
            }

            // Check if referral already exists
            if (Referral::where('referred_by_user_id', $referrer->id)
                ->where('referred_user_id', $referred->id)
                ->exists()) {
                continue;
            }

            $referrals[] = [
                'referred_by_user_id' => $referrer->id,
                'referred_user_id' => $referred->id,
                'referral_code' => $referrer->referral_code,
                'points_awarded' => rand(50, 500),
                'status' => 'active',
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ];

            $referralCount++;

            // Batch insert
            if (count($referrals) >= $batchSize) {
                Referral::insert($referrals);
                echo "Inserted {$referralCount} referrals...\n";
                $referrals = [];
            }
        }

        // Insert remaining
        if (!empty($referrals)) {
            Referral::insert($referrals);
        }

        echo "ReferralSeeder completed! Total referrals: {$referralCount}\n";
    }
}
