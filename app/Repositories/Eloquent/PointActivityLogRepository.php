<?php

namespace App\Repositories\Eloquent;

use App\Models\PointActivityLog;
use App\Repositories\Contracts\PointActivityLogRepositoryInterface;

class PointActivityLogRepository implements PointActivityLogRepositoryInterface
{
    protected $model;

    public function __construct(PointActivityLog $model)
    {
        $this->model = $model;
    }

    public function getUserStatement(int $userId, array $filters = [])
    {
        $query = $this->model->where('user_id', $userId)
                             ->orderBy('earned_at', 'desc');
        
        if (!empty($filters['start_date'])) {
            $query->whereDate('earned_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->whereDate('earned_at', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['activity_code'])) {
            $query->where('activity_code', $filters['activity_code']);
        }
        
        if (!empty($filters['point_status'])) {
            $query->where('point_status', $filters['point_status']);
        }
        
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    public function getActivePoints(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->where('point_status', 'active')
            ->where('expired_at', '>', now())
            ->sum('points_earned');
    }

    public function getPointsExpiringSoon(int $userId, int $days = 30)
    {
        return $this->model->where('user_id', $userId)
            ->where('point_status', 'active')
            ->where('expired_at', '<=', now()->addDays($days))
            ->where('expired_at', '>', now())
            ->sum('points_earned');
    }
}