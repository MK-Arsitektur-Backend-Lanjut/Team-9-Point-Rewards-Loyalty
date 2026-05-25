<?php

namespace Database\Seeders;

use App\Models\MembershipTier;
use Illuminate\Database\Seeder;

class MembershipTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'code' => 'BRONZE',
                'name' => 'Bronze',
                'min_points' => 0,
                'max_points' => 49,
                'point_multiplier' => 1.00,
                'is_active' => true,
            ],
            [
                'code' => 'SILVER',
                'name' => 'Silver',
                'min_points' => 50,
                'max_points' => 99,
                'point_multiplier' => 1.25,
                'is_active' => true,
            ],
            [
                'code' => 'GOLD',
                'name' => 'Gold',
                'min_points' => 100,
                'max_points' => 500,
                'point_multiplier' => 1.50,
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            MembershipTier::query()->updateOrCreate(
                ['code' => $tier['code']],
                $tier
            );
        }
    }
}
