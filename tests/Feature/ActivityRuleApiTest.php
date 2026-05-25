<?php

namespace Tests\Feature;

use App\Models\ActivityRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityRuleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_activity_rule(): void
    {
        $payload = [
            'activity_code' => 'TEST_PURCHASE',
            'name' => 'Test Purchase',
            'point_value' => 10,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/activity-rules', $payload);

        $response->assertCreated()
            ->assertJsonFragment([
                'activity_code' => 'TEST_PURCHASE',
                'name' => 'Test Purchase',
                'point_value' => 10,
            ]);

        $this->assertDatabaseHas('activity_rules', [
            'activity_code' => 'TEST_PURCHASE',
        ]);
    }

    public function test_can_update_activity_rule(): void
    {
        $rule = ActivityRule::query()->create([
            'activity_code' => 'DAILY_LOGIN',
            'name' => 'Login Harian',
            'point_value' => 2,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/activity-rules/{$rule->id}", [
            'point_value' => 5,
            'name' => 'Login Daily Updated',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'point_value' => 5,
                'name' => 'Login Daily Updated',
            ]);

        $this->assertDatabaseHas('activity_rules', [
            'id' => $rule->id,
            'point_value' => 5,
        ]);
    }
}
