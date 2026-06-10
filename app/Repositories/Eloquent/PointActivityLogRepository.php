<?php

namespace App\Repositories\Eloquent;

use App\Models\PointActivityLog;
use App\Repositories\Contracts\PointActivityLogRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PointActivityLogRepository implements PointActivityLogRepositoryInterface
{
    protected $model;

    public function __construct(PointActivityLog $model)
    {
        $this->model = $model;
    }

    public function getUserStatement(int $userId, array $filters = [])
    {
        $cacheKey = 'user_statement_' . $userId . '_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($userId, $filters) {
            $query = $this->model->forUser($userId)
                ->select(['id', 'user_id', 'activity_code', 'points_earned', 'point_status', 'earned_at', 'expired_at'])
                ->orderBy('earned_at', 'desc');

            if (!empty($filters['start_date'])) {
                $query->where('earned_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
            }

            if (!empty($filters['end_date'])) {
                $query->where('earned_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
            }

            if (!empty($filters['activity_code'])) {
                $query->where('activity_code', $filters['activity_code']);
            }

            if (!empty($filters['point_status'])) {
                $query->where('point_status', $filters['point_status']);
            }

            $perPage = $filters['per_page'] ?? 15;

            return $query->paginate($perPage);
        });
    }

    public function getActivePoints(int $userId)
    {
        $cacheKey = "point_activity:user:{$userId}:active_points";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
            return $this->model->forUser($userId)
                ->active()
                ->sum('points_earned');
        });
    }

    public function getPointsExpiringSoon(int $userId, int $days = 30)
    {
        $cacheKey = "point_activity:user:{$userId}:expiring_soon:{$days}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId, $days) {
            return $this->model->forUser($userId)
                ->expiringSoon($days)
                ->sum('points_earned');
        });
    }

    public function markExpiredPoints(int $userId): int
    {
        $updated = $this->model->forUser($userId)
            ->active()
            ->where('expired_at', '<=', now())
            ->update(['point_status' => 'expired']);

        if ($updated) {
            Cache::forget("point_activity:user:{$userId}:active_points");
            Cache::forget("point_activity:user:{$userId}:expiring_soon:30");
        }

        return $updated;
    }
}