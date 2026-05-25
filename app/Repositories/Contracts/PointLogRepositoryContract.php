<?php

namespace App\Repositories\Contracts;

interface PointLogRepositoryContract
{
    /**
     * Create point log entry
     */
    public function create(array $data): object;

    /**
     * Get logs for user
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array;

    /**
     * Get logs by transaction type
     */
    public function getByTransactionType(string $type, int $limit = 50, int $offset = 0): array;

    /**
     * Get all point logs with pagination
     */
    public function getPaginated(int $perPage = 50, int $page = 1): array;

    /**
     * Get total points earned by user
     */
    public function getTotalEarned(int $userId): int;

    /**
     * Get total points redeemed by user
     */
    public function getTotalRedeemed(int $userId): int;

    /**
     * Get point logs between dates
     */
    public function getBetweenDates(int $userId, string $startDate, string $endDate): array;

    /**
     * Count logs by status
     */
    public function countByStatus(string $status): int;

    /**
     * Get all logs with filters
     */
    public function getFiltered(array $filters, int $perPage = 50): array;
}
