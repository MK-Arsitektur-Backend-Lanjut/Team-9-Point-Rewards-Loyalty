<?php

namespace App\Repositories\Contracts;

interface PointActivityLogRepositoryInterface
{
    public function getUserStatement(int $userId, array $filters = []);
    public function getActivePoints(int $userId);
    public function getPointsExpiringSoon(int $userId, int $days = 30);
}