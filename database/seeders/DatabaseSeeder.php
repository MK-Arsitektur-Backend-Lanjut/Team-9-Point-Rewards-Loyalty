<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PointRule;
use App\Models\PointBalance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // HARUS PALING AWAL - karena tabel lain mungkin depend pada membership tiers
            MembershipTierSeeder::class,

            // User & auth related
            UserSeeder::class,

            // Rules & configurations
            PointRuleSeeder::class,
            ActivityRuleSeeder::class,

            // Rewards
            RewardSeeder::class,

            // Transactions & logs (depend on users and rules)
            PointLogSeeder::class,
            ReferralLogSeeder::class,
            PointActivityLogSeeder::class,

            // Referral (depend on users)
            ReferralSeeder::class,
        ]);
    }
}