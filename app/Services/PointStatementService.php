<?php

namespace App\Services;

use App\Repositories\Contracts\PointActivityLogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PointStatementService
{
    protected $pointLogRepository;
    protected $userRepository;

    public function __construct(
        PointActivityLogRepositoryInterface $pointLogRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->pointLogRepository = $pointLogRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Mendapatkan E-Statement lengkap dengan summary
     */
    public function getStatement(int $userId, array $filters = [])
    {
        $user = $this->userRepository->findById($userId);

        $this->pointLogRepository->markExpiredPoints($userId);
        
        // Get paginated statement history
        $history = $this->pointLogRepository->getUserStatement($userId, $filters);
        
        // Cache summary untuk mengurangi beban query Redis/DB
        $summaryCacheKey = sprintf('point_statement:user:%d:summary:%s', $userId, md5(json_encode($filters)));
        $summary = Cache::store('redis')->remember($summaryCacheKey, now()->addMinutes(2), function () use ($userId) {
            $activePoints = $this->pointLogRepository->getActivePoints($userId);

            return [
                'current_balance' => $activePoints,
                'active_points' => $activePoints,
                'points_expiring_soon' => $this->pointLogRepository->getPointsExpiringSoon($userId),
            ];
        });
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'summary' => $summary,
            'history' => $history,
            'generated_at' => Carbon::now()->toDateTimeString()
        ];
    }

    /**
     * Mendapatkan informasi saldo poin
     */
    public function getPointsBalance(int $userId)
    {
        $user = $this->userRepository->findById($userId);
        $this->pointLogRepository->markExpiredPoints($userId);

        $cacheKey = "point_statement:user:{$userId}:balance";

        return Cache::store('redis')->remember($cacheKey, now()->addMinutes(2), function () use ($userId) {
            $activePoints = $this->pointLogRepository->getActivePoints($userId);

            return [
                'current_balance' => $activePoints,
                'active_points' => $activePoints,
                'points_expiring_soon' => $this->pointLogRepository->getPointsExpiringSoon($userId),
                'note' => 'Poin berlaku 1 tahun dari tanggal perolehan'
            ];
        });
    }
}