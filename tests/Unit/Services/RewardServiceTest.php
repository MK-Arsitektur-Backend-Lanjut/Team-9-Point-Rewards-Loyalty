<?php

namespace Tests\Unit\Services;

use App\Models\Reward;
use App\Repositories\Contracts\RewardRepositoryInterface;
use App\Services\RewardService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RewardServiceTest extends TestCase
{
    public function test_reduce_stock_uses_repository_decrement(): void
    {
        $reward = new Reward(['id' => 1, 'stock' => 10]);

        $repository = $this->createMock(RewardRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('decrementStock')
            ->with($reward, 3)
            ->willReturn(true);

        $service = new RewardService($repository);
        $service->reduceStock($reward, 3);

        $this->assertTrue(true);
    }

    public function test_reduce_stock_throws_validation_exception_when_stock_is_not_enough(): void
    {
        $reward = new Reward(['id' => 1, 'stock' => 1]);

        $repository = $this->createMock(RewardRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('decrementStock')
            ->with($reward, 3)
            ->willReturn(false);

        $service = new RewardService($repository);

        $this->expectException(ValidationException::class);
        $service->reduceStock($reward, 3);
    }
}
