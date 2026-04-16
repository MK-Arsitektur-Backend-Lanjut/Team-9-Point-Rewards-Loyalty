<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PointBalance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample users with different tiers
        $tiers = ['bronze', 'silver', 'gold', 'platinum'];
        $multipliers = ['bronze' => 1.0, 'silver' => 1.2, 'gold' => 1.5, 'platinum' => 2.0];

        // Create 1000 users for testing
        for ($i = 0; $i < 1000; $i++) {
            $tier = $tiers[$i % 4];
            $user = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
                'membership_tier' => $tier,
                'referral_code' => 'REF' . strtoupper(Str::random(8)),
                'point_multiplier' => $multipliers[$tier],
            ]);

            // Create point balance for each user
            PointBalance::create([
                'user_id' => $user->id,
                'current_balance' => 0,
                'expired_points' => 0,
                'locked_points' => 0,
                'lifetime_points' => 0,
            ]);

            if ($i % 100 === 0) {
                echo "Created {$i} users...\n";
            }
        }

        echo "UserSeeder completed!\n";
    }
}
