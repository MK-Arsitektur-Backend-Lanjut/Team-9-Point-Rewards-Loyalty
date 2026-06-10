<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MembershipTierSeeder::class,   // HARUS PALING AWAL
            UserSeeder::class,
            ReferralSeeder::class,
            ActivityRuleSeeder::class,
            PointActivityLogSeeder::class,
            RewardSeeder::class,
            PointRuleSeeder::class,
            PointLogSeeder::class,
            ReferralLogSeeder::class,
        ]);
    }
}