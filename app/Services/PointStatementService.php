<?php

namespace App\Services;

use App\Repositories\Contracts\PointActivityLogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;

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
        
        // Get paginated statement history
        $history = $this->pointLogRepository->getUserStatement($userId, $filters);
        
        // Calculate summary (dari data Modul 1 & 2)
        $summary = [
            'current_balance' => $user->points_balance,
            'active_points' => $this->pointLogRepository->getActivePoints($userId),
            'points_expiring_soon' => $this->pointLogRepository->getPointsExpiringSoon($userId),
        ];
        
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
        
        return [
            'current_balance' => $user->points_balance,
            'active_points' => $this->pointLogRepository->getActivePoints($userId),
            'points_expiring_soon' => $this->pointLogRepository->getPointsExpiringSoon($userId),
            'note' => 'Poin berlaku 1 tahun dari tanggal perolehan'
        ];
    }
}