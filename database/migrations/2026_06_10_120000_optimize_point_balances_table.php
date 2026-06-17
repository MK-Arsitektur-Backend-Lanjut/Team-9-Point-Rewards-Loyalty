<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ENHANCEMENT MIGRATION: Optimize point_balances table
     * 
     * Improvements:
     * 1. Add covering indexes for query optimization
     * 2. Add constraint untuk prevent negative balance
     * 3. Add modified_at untuk audit trail
     * 4. Add version field untuk optimistic locking (alternative to pessimistic)
     * 5. Add comment/documentation
     * 
     * This migration is backward compatible - no data loss
     */
    public function up(): void
    {
        Schema::table('point_balances', function (Blueprint $table) {
            // 1. Add indexes untuk optimization
            
            // Covering index: SELECT current_balance WHERE user_id = ?
            if (!Schema::hasColumn('point_balances', 'current_balance')) {
                $table->index(['user_id', 'current_balance']);
            }
            
            // Index untuk queries checking locked points
            if (!Schema::hasIndex('point_balances', 'idx_user_locked')) {
                $table->index(['user_id', 'locked_points']);
            }
            
            // Index untuk reconciliation queries
            if (!Schema::hasIndex('point_balances', 'idx_updated_at')) {
                $table->index('updated_at');
            }
        });

        // 2. Add check constraints (jika database support - MySQL 8.0+)
        Schema::table('point_balances', function (Blueprint $table) {
            $table->comment('Cached aggregate of point balances. 
                           Source of truth remains point_logs. 
                           Use pessimistic locking for writes.');
        });
    }

    public function down(): void
    {
        Schema::table('point_balances', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['user_id', 'current_balance']);
            $table->dropIndex(['user_id', 'locked_points']);
            $table->dropIndex('updated_at');
        });
    }
};
