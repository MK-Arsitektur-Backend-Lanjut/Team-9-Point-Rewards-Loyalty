<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityRule;
use Illuminate\Database\Eloquent\Collection;

interface ActivityRuleRepositoryInterface
{
    public function getAll(): Collection;

    public function create(array $data): ActivityRule;

    public function update(ActivityRule $activityRule, array $data): ActivityRule;

    public function delete(ActivityRule $activityRule): void;
}
