<?php

namespace Database\Seeders;

use App\Models\ActivityRule;
use Illuminate\Database\Seeder;

class ActivityRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['activity_code' => 'TRX_PURCHASE', 'name' => 'Transaksi Belanja', 'point_value' => 10],
            ['activity_code' => 'DAILY_LOGIN', 'name' => 'Login Harian', 'point_value' => 2],
            ['activity_code' => 'PROFILE_COMPLETED', 'name' => 'Lengkapi Profil', 'point_value' => 20],
            ['activity_code' => 'REVIEW_PRODUCT', 'name' => 'Review Produk', 'point_value' => 5],
            ['activity_code' => 'BIRTHDAY_BONUS', 'name' => 'Bonus Ulang Tahun', 'point_value' => 50],
        ];

        foreach ($rules as $rule) {
            ActivityRule::query()->updateOrCreate(
                ['activity_code' => $rule['activity_code']],
                array_merge($rule, ['is_active' => true])
            );
        }
    }
}
