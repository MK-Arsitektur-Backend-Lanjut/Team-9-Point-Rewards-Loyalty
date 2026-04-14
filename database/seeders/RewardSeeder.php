<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            ['sku' => 'VOUCHER-10K', 'name' => 'Voucher Diskon 10K', 'points_required' => 100, 'stock' => 500, 'is_physical' => false],
            ['sku' => 'MUG-LOYALTY', 'name' => 'Mug Eksklusif', 'points_required' => 250, 'stock' => 120, 'is_physical' => true],
            ['sku' => 'TSHIRT-LOYALTY', 'name' => 'T-Shirt Loyalty', 'points_required' => 350, 'stock' => 90, 'is_physical' => true],
            ['sku' => 'TUMBLER-500ML', 'name' => 'Tumbler 500ml', 'points_required' => 300, 'stock' => 80, 'is_physical' => true],
            ['sku' => 'VOUCHER-50K', 'name' => 'Voucher Diskon 50K', 'points_required' => 450, 'stock' => 250, 'is_physical' => false],
        ];

        foreach ($rewards as $reward) {
            Reward::query()->updateOrCreate(
                ['sku' => $reward['sku']],
                array_merge($reward, ['is_active' => true])
            );
        }
    }
}
