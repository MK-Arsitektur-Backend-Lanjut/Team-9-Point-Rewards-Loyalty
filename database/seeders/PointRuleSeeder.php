<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PointRule;

class PointRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'rule_name' => 'Purchase',
                'description' => '1 poin per 1000 rupiah pembelian',
                'base_points' => 10,
                'multiplier_rules' => [
                    'bronze' => 1.0,
                    'silver' => 1.2,
                    'gold' => 1.5,
                    'platinum' => 2.0,
                ],
                'validity_days' => 365,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Sign Up',
                'description' => 'Bonus poin saat registrasi',
                'base_points' => 100,
                'multiplier_rules' => [
                    'bronze' => 1.0,
                    'silver' => 1.0,
                    'gold' => 1.0,
                    'platinum' => 1.0,
                ],
                'validity_days' => 365,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Review Product',
                'description' => 'Poin untuk menulis review produk',
                'base_points' => 50,
                'multiplier_rules' => [
                    'bronze' => 1.0,
                    'silver' => 1.2,
                    'gold' => 1.5,
                    'platinum' => 2.0,
                ],
                'validity_days' => 365,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Referral Bonus',
                'description' => 'Bonus poin ketika merekomendasikan teman',
                'base_points' => 200,
                'multiplier_rules' => [
                    'bronze' => 1.0,
                    'silver' => 1.3,
                    'gold' => 1.6,
                    'platinum' => 2.2,
                ],
                'validity_days' => 365,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Birthday Bonus',
                'description' => 'Bonus poin di hari ulang tahun member',
                'base_points' => 150,
                'multiplier_rules' => [
                    'bronze' => 1.0,
                    'silver' => 1.1,
                    'gold' => 1.2,
                    'platinum' => 1.5,
                ],
                'validity_days' => 365,
                'is_active' => true,
            ],
            [
                'rule_name' => 'Social Share',
                'description' => 'Poin untuk berbagi di media sosial',
                'base_points' => 75,
                'multiplier_rules' => [
                    'bronze' => 1.0,
                    'silver' => 1.15,
                    'gold' => 1.4,
                    'platinum' => 1.8,
                ],
                'validity_days' => 365,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            PointRule::create($rule);
        }

        echo "PointRuleSeeder completed!\n";
    }
}
