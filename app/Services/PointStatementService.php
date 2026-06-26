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

    private const CACHE_PREFIX = 'point_statement:';
    private const CACHE_TTL = 60; // 1 menit

    public function __construct(
        PointActivityLogRepositoryInterface $pointLogRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->pointLogRepository = $pointLogRepository;
        $this->userRepository = $userRepository;
    }

    public function getStatement(int $userId, array $filters = [])
    {
        $user = $this->userRepository->findById($userId);
        $this->pointLogRepository->markExpiredPoints($userId);
        
        $history = $this->pointLogRepository->getUserStatement($userId, $filters);
        
        // GUNAKAN FILE CACHE (BUKAN REDIS)
        $summaryCacheKey = sprintf('%suser:%d:summary:%s', self::CACHE_PREFIX, $userId, md5(json_encode($filters)));
        
        $summary = Cache::remember($summaryCacheKey, self::CACHE_TTL, function () use ($userId) {
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

    public function getPointsBalance(int $userId)
    {
        $user = $this->userRepository->findById($userId);
        $this->pointLogRepository->markExpiredPoints($userId);

        $cacheKey = self::CACHE_PREFIX . "user:{$userId}:balance";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $activePoints = $this->pointLogRepository->getActivePoints($userId);

            return [
                'current_balance' => $activePoints,
                'active_points' => $activePoints,
                'points_expiring_soon' => $this->pointLogRepository->getPointsExpiringSoon($userId),
                'note' => 'Poin berlaku 1 tahun dari tanggal perolehan'
            ];
        });
    }

    public function clearCache(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . "user:{$userId}:balance");
        // File cache tidak support pattern delete, jadi clear manual
        Cache::forget(self::CACHE_PREFIX . "user:{$userId}:summary");
    }
}