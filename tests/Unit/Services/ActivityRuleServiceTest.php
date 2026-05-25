<?php

namespace Tests\Unit\Services;

use App\Models\ActivityRule;
use App\Repositories\Contracts\ActivityRuleRepositoryInterface;
use App\Services\ActivityRuleService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class ActivityRuleServiceTest extends TestCase
{
    public function test_list_delegates_to_repository(): void
    {
        $expected = new Collection();

        $repository = $this->createMock(ActivityRuleRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getAll')
            ->willReturn($expected);

        $service = new ActivityRuleService($repository);

        $this->assertSame($expected, $service->list());
    }

    public function test_create_delegates_to_repository(): void
    {
        $payload = [
            'activity_code' => 'DAILY_LOGIN',
            'name' => 'Login Harian',
            'point_value' => 2,
        ];

        $model = new ActivityRule($payload);

        $repository = $this->createMock(ActivityRuleRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('create')
            ->with($payload)
            ->willReturn($model);

        $service = new ActivityRuleService($repository);

        $this->assertSame($model, $service->create($payload));
    }
}
