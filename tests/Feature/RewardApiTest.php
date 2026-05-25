<?php

namespace Tests\Feature;

use App\Models\Reward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reward(): void
    {
        $payload = [
            'sku' => 'TEST-SKU-001',
            'name' => 'Reward Test',
            'points_required' => 150,
            'stock' => 20,
            'is_physical' => true,
        ];

        $response = $this->postJson('/api/rewards', $payload);

        $response->assertCreated()
            ->assertJsonFragment([
                'sku' => 'TEST-SKU-001',
                'name' => 'Reward Test',
            ]);

        $this->assertDatabaseHas('rewards', [
            'sku' => 'TEST-SKU-001',
        ]);
    }

    public function test_decrement_stock_successfully(): void
    {
        $reward = Reward::query()->create([
            'sku' => 'SKU-STOCK-001',
            'name' => 'Stock Reward',
            'points_required' => 100,
            'stock' => 10,
            'is_physical' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/rewards/{$reward->id}/decrement-stock", [
            'quantity' => 3,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Stock berhasil dikurangi.']);

        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'stock' => 7,
        ]);
    }

    public function test_decrement_stock_returns_validation_error_when_not_enough(): void
    {
        $reward = Reward::query()->create([
            'sku' => 'SKU-STOCK-002',
            'name' => 'Low Stock Reward',
            'points_required' => 100,
            'stock' => 2,
            'is_physical' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/rewards/{$reward->id}/decrement-stock", [
            'quantity' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock']);

        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'stock' => 2,
        ]);
    }
}
