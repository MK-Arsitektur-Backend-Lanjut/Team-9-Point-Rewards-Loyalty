<?php

namespace Database\Seeders;

use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tierIds = DB::table('membership_tiers')->pluck('id');

        if ($tierIds->isEmpty()) {
            echo "No tiers found!\n";
            return;
        }

        // Buat 1000 user dengan loop
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
        
        // Buat user demo spesifik
        $tiers = MembershipTier::query()->orderBy('min_points')->get();
        $now = now();

        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'points' => 500, 'referral_seeded' => true],
            ['name' => 'John Doe', 'email' => 'john@example.com', 'points' => 250, 'referral_seeded' => true],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'points' => 150, 'referral_seeded' => true],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'points' => 75, 'referral_seeded' => true],
            ['name' => 'Alice Williams', 'email' => 'alice@example.com', 'points' => 300, 'referral_seeded' => true],
            ['name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'points' => 120, 'referral_seeded' => false],
            ['name' => 'Diana Prince', 'email' => 'diana@example.com', 'points' => 400, 'referral_seeded' => true],
            ['name' => 'Edward Norton', 'email' => 'edward@example.com', 'points' => 85, 'referral_seeded' => false],
            ['name' => 'Fiona Apple', 'email' => 'fiona@example.com', 'points' => 200, 'referral_seeded' => true],
            ['name' => 'George Miller', 'email' => 'george@example.com', 'points' => 180, 'referral_seeded' => false],
            ['name' => 'Hannah Montana', 'email' => 'hannah@example.com', 'points' => 350, 'referral_seeded' => true],
            ['name' => 'Ivan Petrov', 'email' => 'ivan@example.com', 'points' => 45, 'referral_seeded' => false],
            ['name' => 'Julia Roberts', 'email' => 'julia@example.com', 'points' => 280, 'referral_seeded' => true],
            ['name' => 'Kevin Hart', 'email' => 'kevin@example.com', 'points' => 95, 'referral_seeded' => false],
            ['name' => 'Laura Palmer', 'email' => 'laura@example.com', 'points' => 320, 'referral_seeded' => true],
        ];

        $referrers = [];

        foreach ($users as $userData) {
            $tier = $this->resolveTierByPoints($tiers, $userData['points']);

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'email_verified_at' => $now,
                'password' => Hash::make('password123'),
                'points' => $userData['points'],
                'points_balance' => $userData['points'],
                'membership_tier_id' => $tier?->id,
                'referral_code' => null,
            ]);

            // Generate referral code untuk beberapa user
            if ($userData['referral_seeded']) {
                $referralCode = sprintf('RF%d%s', $user->id, strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));
                $user->update(['referral_code' => $referralCode]);
                $referrers[] = $user->id;
            }

            echo "✓ Created user: {$user->name} (ID: {$user->id}, Points: {$user->points}, Tier: {$tier?->name})\n";
        }

        // Buat relasi referral untuk beberapa users
        if (count($referrers) >= 2) {
            $this->linkUsersAsReferrals($referrers);
        }

        echo "\n✓ UserSeeder selesai: " . count($users) . " demo users created\n";
    }

    private function resolveTierByPoints($tiers, int $points): ?MembershipTier
    {
        foreach ($tiers->reverse() as $tier) {
            if ($points >= $tier->min_points) {
                if ($tier->max_points === null || $points <= $tier->max_points) {
                    return $tier;
                }
            }
        }

        return $tiers->first();
    }

    private function linkUsersAsReferrals(array $referrerIds): void
    {
        // Link beberapa user sebagai referee dari referrer
        $users = User::query()->whereNull('referred_by_user_id')->get();
        $referrerIndex = 0;

        foreach ($users->skip(count($referrerIds)) as $user) {
            $referrer = User::query()->find($referrerIds[$referrerIndex % count($referrerIds)]);

            if ($referrer && $referrer->id !== $user->id && is_null($user->referred_by_user_id)) {
                $user->update([
                    'referred_by_user_id' => $referrer->id,
                    'points' => $user->points + 25,
                ]);

                $referrer->increment('points', 50);
                $referrerIndex++;

                echo "  ↳ {$user->name} dipandu oleh {$referrer->name}\n";
            }
        }
    }
}