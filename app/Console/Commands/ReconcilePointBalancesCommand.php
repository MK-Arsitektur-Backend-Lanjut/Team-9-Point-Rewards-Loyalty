<?php

namespace App\Console\Commands;

use App\Services\PointBalanceReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan Command: Reconcile Point Balances
 * 
 * Dijalankan via:
 * - php artisan points:reconcile --type=batch   (hourly via schedule)
 * - php artisan points:reconcile --type=full    (weekly via schedule)
 * - php artisan points:reconcile --type=check   (manual inspection)
 * 
 * Usage di scheduler (app/Console/Kernel.php):
 * 
 *   $schedule->command('points:reconcile --type=batch')
 *            ->hourly();
 * 
 *   $schedule->command('points:reconcile --type=full')
 *            ->weekly()
 *            ->sundays()
 *            ->at('02:00');
 */
class ReconcilePointBalancesCommand extends Command
{
    protected $signature = 'points:reconcile
                          {--type=batch : Type of reconciliation (batch, full, check)}
                          {--user-id= : Optional specific user ID}
                          {--sample-size=10 : Sample size percentage for batch}';

    protected $description = 'Reconcile point balances antara cache dan source of truth';

    public function __construct(
        private PointBalanceReconciliationService $reconciliationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = $this->option('type');
        
        $this->info("Starting point balance reconciliation: {$type}");

        try {
            match ($type) {
                'batch' => $this->batchReconciliation(),
                'full' => $this->fullReconciliation(),
                'check' => $this->checkCompleteness(),
                default => $this->error("Invalid type: {$type}")
            };

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Reconciliation failed: {$e->getMessage()}");
            Log::error('Points reconciliation failed', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Batch reconciliation: Check 10% of users (default)
     */
    private function batchReconciliation(): void
    {
        $sampleSize = (int)$this->option('sample-size');
        
        $this->info("Running batch reconciliation (sample size: {$sampleSize}%)...");

        $result = $this->reconciliationService->batchReconciliation($sampleSize);

        $this->line('');
        $this->line('═══════════════════════════════════════');
        $this->line('Batch Reconciliation Result:');
        $this->line('═══════════════════════════════════════');
        $this->line("Total checked:     {$result['total_checked']} users");
        $this->line("Consistent:        {$result['consistent']} ✓");
        $this->line("Inconsistent:      {$result['inconsistent']} ✗");
        $this->line("Consistency Rate:  {$result['consistency_rate']}%");
        $this->line('═══════════════════════════════════════');

        if ($result['inconsistent'] > 0) {
            $this->line('');
            $this->warn('Inconsistencies found:');
            foreach ($result['discrepancies'] as $disc) {
                $this->warn("  User {$disc['user_id']}: {$disc['difference']} points difference");
            }
        }
    }

    /**
     * Full reconciliation: Check all users
     */
    private function fullReconciliation(): void
    {
        $this->info('Running full reconciliation...');
        $this->warn('This is a heavy operation. Progress will be updated periodically.');

        $result = $this->reconciliationService->fullReconciliation();

        $this->line('');
        $this->line('═══════════════════════════════════════');
        $this->line('Full Reconciliation Result:');
        $this->line('═══════════════════════════════════════');
        $this->line("Total users:          {$result['total_users']}");
        $this->line("Consistent:           {$result['consistent']} ✓");
        $this->line("Inconsistent:         {$result['inconsistent']} ✗");
        $this->line("Auto-corrected:       {$result['auto_corrected']} ↻");
        $this->line("Consistency Rate:     {$result['consistency_rate']}%");
        $this->line("Total Discrepancy:    {$result['total_discrepancy_amount']} points");
        $this->line('═══════════════════════════════════════');

        if (count($result['manual_review_needed']) > 0) {
            $this->line('');
            $this->alert('MANUAL REVIEW NEEDED:');
            $this->line('The following users have significant discrepancies:');
            $this->line('');
            
            foreach ($result['manual_review_needed'] as $item) {
                $diff = $item['difference'];
                $color = $diff > 0 ? 'fg=yellow' : 'fg=red';
                $this->line("  User {$item['user_id']}: cached={$item['cached']}, "
                          . "calculated={$item['calculated']}, diff={$diff}", $color);
            }
        }
    }

    /**
     * Check logs completeness
     */
    private function checkCompleteness(): void
    {
        $this->info('Checking point logs completeness...');

        $result = $this->reconciliationService->checkLogsCompleteness();

        $this->line('');
        $this->line('═══════════════════════════════════════');
        $this->line('Logs Completeness Check:');
        $this->line('═══════════════════════════════════════');
        $this->line("Orphaned logs (users):    {$result['orphaned_logs_users']}");
        $this->line("Unused balances:          {$result['unused_balances']}");
        $this->line('═══════════════════════════════════════');

        if ($result['orphaned_logs_users'] > 0) {
            $this->warn('Warning: Found orphaned logs!');
            foreach ($result['details']['orphaned'] as $log) {
                $this->warn("  User {$log->user_id}: {$log->log_count} logs");
            }
        }
    }
}
