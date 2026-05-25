<?php

namespace Tests\Feature;

use App\Models\ActivityRule;
use App\Models\MembershipTier;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipModuleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_activity_trigger_applies_multiplier(): void
    {
        $tier = MembershipTier::query()->create([
            'code' => 'SILVER',
            'name' => 'Silver',
            'min_points' => 0,
            'max_points' => null,
            'point_multiplier' => 1.50,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'points' => 0,
            'membership_tier_id' => $tier->id,
        ]);

        ActivityRule::query()->create([
            'activity_code' => 'DAILY_LOGIN',
            'name' => 'Daily Login',
            'point_value' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/membership/activity/trigger', [
            'user_id' => $user->id,
            'activity_code' => 'DAILY_LOGIN',
        ]);

        $response->assertOk()->assertJson([
            'points_added' => 15,
            'total_points' => 15,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'points' => 15,
        ]);

        $this->assertDatabaseHas('point_activity_logs', [
            'user_id' => $user->id,
            'activity_code' => 'DAILY_LOGIN',
            'points_earned' => 15,
        ]);
    }

    public function test_referral_apply_rewards_both_users_and_logs(): void
    {
        $referrer = User::factory()->create([
            'points' => 0,
            'referral_code' => 'RFTEST123',
        ]);

        $referee = User::factory()->create([
            'points' => 0,
        ]);

        $response = $this->postJson('/api/membership/referrals/apply', [
            'user_id' => $referee->id,
            'referral_code' => 'RFTEST123',
        ]);

        $response->assertOk()->assertJsonPath('referrer.bonus_points', 50)
            ->assertJsonPath('referee.bonus_points', 25);

        $this->assertDatabaseHas('users', [
            'id' => $referrer->id,
            'points' => 50,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $referee->id,
            'points' => 25,
            'referred_by_user_id' => $referrer->id,
        ]);

        $this->assertDatabaseHas('referral_logs', [
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $referee->id,
            'referral_code' => 'RFTEST123',
        ]);

        $this->assertDatabaseHas('point_activity_logs', [
            'user_id' => $referrer->id,
            'activity_code' => 'referral_bonus_referrer',
            'points_earned' => 50,
        ]);

        $this->assertDatabaseHas('point_activity_logs', [
            'user_id' => $referee->id,
            'activity_code' => 'referral_bonus_referee',
            'points_earned' => 25,
        ]);
    }

    public function test_reward_redeem_deducts_points_and_stock(): void
    {
        $user = User::factory()->create([
            'points' => 100,
        ]);

        $reward = Reward::query()->create([
            'sku' => 'RW-001',
            'name' => 'Voucher 50K',
            'points_required' => 20,
            'stock' => 5,
            'is_physical' => false,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/membership/rewards/{$reward->id}/redeem", [
            'user_id' => $user->id,
            'quantity' => 2,
        ]);

        $response->assertOk()->assertJson([
            'points_spent' => 40,
            'remaining_points' => 60,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'points' => 60,
        ]);

        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'stock' => 3,
        ]);

        $this->assertDatabaseHas('reward_redemptions', [
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'quantity' => 2,
            'points_spent' => 40,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('point_activity_logs', [
            'user_id' => $user->id,
            'activity_code' => 'reward_redeem',
            'points_earned' => 0,
        ]);
    }

    public function test_delete_tier_endpoint_removes_tier_and_nulls_user_tier_reference(): void
    {
        $tier = MembershipTier::query()->create([
            'code' => 'GOLD',
            'name' => 'Gold',
            'min_points' => 1000,
            'max_points' => 4999,
            'point_multiplier' => 1.50,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'membership_tier_id' => $tier->id,
        ]);

        $response = $this->deleteJson("/api/membership/tiers/{$tier->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('membership_tiers', [
            'id' => $tier->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'membership_tier_id' => null,
        ]);
    }
}
