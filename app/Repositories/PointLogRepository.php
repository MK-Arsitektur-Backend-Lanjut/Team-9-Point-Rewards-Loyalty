<?php

namespace App\Repositories;

use App\Models\PointLog;
use App\Repositories\Contracts\PointLogRepositoryContract;

class PointLogRepository implements PointLogRepositoryContract
{
    /**
     * Create point log entry
     */
    public function create(array $data): object
    {
        return PointLog::create($data);
    }

    /**
     * Get logs for user
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return PointLog::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    /**
     * Get logs by transaction type
     */
    public function getByTransactionType(string $type, int $limit = 50, int $offset = 0): array
    {
        return PointLog::where('transaction_type', $type)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    /**
     * Get all point logs with pagination
     */
    public function getPaginated(int $perPage = 50, int $page = 1): array
    {
        $logs = PointLog::orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ]
        ];
    }

    /**
     * Get total points earned by user
     */
    public function getTotalEarned(int $userId): int
    {
        return (int) PointLog::where('user_id', $userId)
            ->where('transaction_type', PointLog::TRANSACTION_EARN)
            ->where('status', PointLog::STATUS_COMPLETED)
            ->sum('points_amount');
    }

    /**
     * Get total points redeemed by user
     */
    public function getTotalRedeemed(int $userId): int
    {
        return (int) PointLog::where('user_id', $userId)
            ->where('transaction_type', PointLog::TRANSACTION_REDEEM)
            ->where('status', PointLog::STATUS_COMPLETED)
            ->sum('points_amount');
    }

    /**
     * Get point logs between dates
     */
    public function getBetweenDates(int $userId, string $startDate, string $endDate): array
    {
        return PointLog::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Count logs by status
     */
    public function countByStatus(string $status): int
    {
        return PointLog::where('status', $status)->count();
    }

    /**
     * Get all logs with filters
     */
    public function getFiltered(array $filters, int $perPage = 50): array
    {
        $query = PointLog::query();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'],
                $filters['end_date']
            ]);
        }

        $logs = $query->orderByDesc('created_at')->paginate($perPage);

        return [
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ]
        ];
    }
}
