<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tierIds = DB::table('membership_tiers')->pluck('id');

        if ($tierIds->isEmpty()) {
            echo "No tiers found!\n";
            return;
        }

        for ($i = 0; $i < 1000; $i++) {
            $tierId = $tierIds->random();

            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
                'membership_tier_id' => $tierId,
                'referral_code' => 'REF' . strtoupper(Str::random(8)),
                'referred_by_user_id' => null,
            ]);

            if ($i % 100 === 0) {
                echo "Created {$i} users...\n";
            }
        }

        echo "UserSeeder completed!\n";
    }
}