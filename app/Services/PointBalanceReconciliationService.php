<?php

namespace App\Services;

use App\Models\PointBalance;
use App\Models\PointLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Point Balance Reconciliation Service
 * 
 * Purpose: Maintain data consistency antara:
 * - Cached aggregate (point_balances.current_balance)
 * - Source of truth (SUM dari point_logs)
 * 
 * Strategi: Three-tier verification
 * 1. Real-time: Pessimistic lock during transaction
 * 2. Periodic: Batch reconciliation (Hourly/Daily)
 * 3. Full: Weekly comprehensive check
 * 
 * Risks mitigated:
 * - Manual database updates
 * - Bug dalam service layer
 * - Incomplete transactions
 * - Data corruption dari external sources
 */
class PointBalanceReconciliationService
{
    /**
     * TIER 2: Batch reconciliation (run hourly via cron)
     * 
     * Rekonsiliasi sample random users untuk detect anomalies early
     * Default: check 10% of users
     * 
     * @param int $percentSampleSize
     * @return array
     */
    public function batchReconciliation(int $percentSampleSize = 10): array
    {
        Log::info('Starting batch reconciliation', ['sample_size_percent' => $percentSampleSize]);

        try {
            // 1. Get sample of users (random)
            $totalUsers = PointBalance::count();
            $sampleSize = max(1, intval($totalUsers * ($percentSampleSize / 100)));

            $balances = PointBalance::inRandomOrder()
                ->limit($sampleSize)
                ->get();

            $results = [
                'total_checked' => 0,
                'consistent' => 0,
                'inconsistent' => 0,
                'discrepancies' => []
            ];

            // 2. Check each balance
            foreach ($balances as $balance) {
                $reconciliation = $this->reconcileSingleUser($balance->user_id);
                $results['total_checked']++;

                if ($reconciliation['is_consistent']) {
                    $results['consistent']++;
                } else {
                    $results['inconsistent']++;
                    
                    // Log discrepancy jika difference significant (> 1%)
                    $percentDiff = abs($reconciliation['difference']) / 
                                  max(1, $reconciliation['cached']) * 100;
                    
                    if ($percentDiff > 1) {
                        $results['discrepancies'][] = $reconciliation;
                        
                        // Alert untuk significant discrepancies
                        Log::warning('Significant balance discrepancy detected', [
                            'user_id' => $balance->user_id,
                            'cached' => $reconciliation['cached'],
                            'calculated' => $reconciliation['calculated'],
                            'difference' => $reconciliation['difference'],
                            'percent_diff' => round($percentDiff, 2)
                        ]);
                    }
                }
            }

            $results['consistency_rate'] = round(
                ($results['consistent'] / max(1, $results['total_checked'])) * 100,
                2
            );

            Log::info('Batch reconciliation completed', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('Batch reconciliation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * TIER 3: Full reconciliation (run weekly)
     * 
     * Check ALL users untuk comprehensive audit
     * Heavier operation - run saat off-peak hours
     * 
     * @return array
     */
    public function fullReconciliation(): array
    {
        Log::info('Starting full reconciliation');

        try {
            $balances = PointBalance::all();

            $results = [
                'total_users' => 0,
                'consistent' => 0,
                'inconsistent' => 0,
                'auto_corrected' => 0,
                'manual_review_needed' => [],
                'total_discrepancy_amount' => 0
            ];

            foreach ($balances as $balance) {
                $reconciliation = $this->reconcileSingleUser($balance->user_id);
                $results['total_users']++;

                if ($reconciliation['is_consistent']) {
                    $results['consistent']++;
                } else {
                    $results['inconsistent']++;
                    $results['total_discrepancy_amount'] += abs($reconciliation['difference']);

                    $percentDiff = abs($reconciliation['difference']) / 
                                  max(1, $reconciliation['cached']) * 100;

                    // Auto-correct jika discrepancy kecil (< 1% dan < 100 points)
                    if ($percentDiff < 1 && abs($reconciliation['difference']) < 100) {
                        $this->autoCorrectBalance(
                            $balance->user_id,
                            $reconciliation['calculated']
                        );
                        $results['auto_corrected']++;
                        
                        Log::info('Auto-corrected balance', [
                            'user_id' => $balance->user_id,
                            'from' => $reconciliation['cached'],
                            'to' => $reconciliation['calculated']
                        ]);
                    } else {
                        // Large discrepancy: requires manual review
                        $results['manual_review_needed'][] = [
                            'user_id' => $balance->user_id,
                            'cached' => $reconciliation['cached'],
                            'calculated' => $reconciliation['calculated'],
                            'difference' => $reconciliation['difference']
                        ];
                        
                        Log::alert('Manual review needed for balance discrepancy', [
                            'user_id' => $balance->user_id,
                            'difference' => $reconciliation['difference']
                        ]);
                    }
                }
            }

            $results['consistency_rate'] = round(
                (($results['consistent'] + $results['auto_corrected']) / max(1, $results['total_users'])) * 100,
                2
            );

            Log::info('Full reconciliation completed', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('Full reconciliation failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reconcile single user
     * 
     * Compare:
     * - Cached: point_balances.current_balance
     * - Calculated: SUM(point_logs) untuk user
     * 
     * @param int $userId
     * @return array
     */
    private function reconcileSingleUser(int $userId): array
    {
        // 1. Get cached value
        $pointBalance = PointBalance::where('user_id', $userId)->first();
        $cached = $pointBalance ? $pointBalance->current_balance : 0;

        // 2. Calculate dari source of truth
        $calculated = DB::table('point_logs')
            ->where('user_id', $userId)
            ->where('status', PointLog::STATUS_COMPLETED)
            ->selectRaw('
                COALESCE(SUM(
                    CASE WHEN transaction_type IN (?, ?, ?) 
                    THEN points_amount ELSE 0 END
                ), 0) - 
                COALESCE(SUM(
                    CASE WHEN transaction_type IN (?, ?) 
                    THEN points_amount ELSE 0 END
                ), 0) as calculated_balance
            ', [
                PointLog::TRANSACTION_EARN,
                PointLog::TRANSACTION_REFERRAL,
                PointLog::TRANSACTION_ADJUSTMENT,
                PointLog::TRANSACTION_REDEEM,
                PointLog::TRANSACTION_EXPIRE
            ])
            ->first()
            ->calculated_balance ?? 0;

        // 3. Compare
        $difference = $cached - (int)$calculated;

        return [
            'user_id' => $userId,
            'cached' => $cached,
            'calculated' => (int)$calculated,
            'difference' => $difference,
            'is_consistent' => $difference === 0,
            'timestamp' => now()
        ];
    }

    /**
     * Auto-correct balance untuk small discrepancies
     * 
     * Creates audit log untuk track correction
     * 
     * @param int $userId
     * @param int $correctValue
     * @return void
     */
    private function autoCorrectBalance(int $userId, int $correctValue): void
    {
        DB::transaction(function () use ($userId, $correctValue) {
            $balance = PointBalance::where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                return;
            }

            $oldValue = $balance->current_balance;

            // Update balance
            PointBalance::where('user_id', $userId)
                ->update(['current_balance' => $correctValue]);

            // Create audit log (untuk track corrections)
            PointLog::create([
                'user_id' => $userId,
                'points_amount' => abs($correctValue - $oldValue),
                'transaction_type' => PointLog::TRANSACTION_ADJUSTMENT,
                'description' => "Auto-correction from reconciliation: {$oldValue} → {$correctValue}",
                'reference_id' => 'reconciliation_' . uniqid(),
                'metadata' => [
                    'old_value' => $oldValue,
                    'new_value' => $correctValue,
                    'reason' => 'auto_correction'
                ],
                'status' => PointLog::STATUS_COMPLETED,
                'created_at' => now()
            ]);
        });
    }

    /**
     * Check point_logs completeness
     * 
     * Verify bahwa tidak ada orphaned transactions:
     * - Transactions tanpa corresponding balance record
     * - Orphaned logs dari deleted users
     * 
     * @return array
     */
    public function checkLogsCompleteness(): array
    {
        Log::info('Checking point logs completeness');

        // 1. Find logs tanpa balance record
        $orphanedLogs = DB::select("
            SELECT pl.user_id, COUNT(*) as log_count
            FROM point_logs pl
            LEFT JOIN point_balances pb ON pl.user_id = pb.user_id
            WHERE pb.id IS NULL
            GROUP BY pl.user_id
        ");

        // 2. Find balance records tanpa logs
        $unusedBalances = DB::select("
            SELECT pb.user_id, pb.current_balance
            FROM point_balances pb
            LEFT JOIN point_logs pl ON pb.user_id = pl.user_id
            WHERE pl.id IS NULL
            AND pb.current_balance = 0
        ");

        $results = [
            'orphaned_logs_users' => count($orphanedLogs),
            'unused_balances' => count($unusedBalances),
            'details' => [
                'orphaned' => $orphanedLogs,
                'unused' => $unusedBalances
            ]
        ];

        if (count($orphanedLogs) > 0) {
            Log::warning('Found orphaned logs', $results);
        }

        return $results;
    }

    /**
     * Generate reconciliation report untuk presentation
     * 
     * @return array
     */
    public function generateReport(): array
    {
        return [
            'generated_at' => now(),
            'total_users' => PointBalance::count(),
            'total_balance' => PointBalance::sum('current_balance'),
            'active_users' => PointBalance::where('current_balance', '>', 0)->count(),
            'locked_points_total' => PointBalance::sum('locked_points'),
            'lifetime_points_total' => PointBalance::sum('lifetime_points'),
            'transaction_count' => PointLog::count(),
            'recent_batch_reconciliation' => $this->batchReconciliation(10),
            'logs_completeness' => $this->checkLogsCompleteness()
        ];
    }
}
