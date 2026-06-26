<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================
        // 1. INDEX UNTUK LOGIN (PALING PENTING!)
        // ============================================
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', 'idx_users_email_password')) {
                $table->index(['email', 'password'], 'idx_users_email_password');
            }
            
            if (!Schema::hasIndex('users', 'idx_users_email')) {
                $table->index('email', 'idx_users_email');
            }
        });

        // ============================================
        // 2. INDEX UNTUK POINT ACTIVITY LOGS
        // ============================================
        Schema::table('point_activity_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('point_activity_logs', 'idx_point_activity_logs_user_earned_at')) {
                $table->index(['user_id', 'earned_at'], 'idx_point_activity_logs_user_earned_at');
            }
            
            if (!Schema::hasIndex('point_activity_logs', 'idx_point_activity_logs_user_status_expired')) {
                $table->index(['user_id', 'point_status', 'expired_at'], 'idx_point_activity_logs_user_status_expired');
            }
            
            if (!Schema::hasIndex('point_activity_logs', 'idx_point_activity_logs_user_activity_code')) {
                $table->index(['user_id', 'activity_code'], 'idx_point_activity_logs_user_activity_code');
            }
        });

        // ============================================
        // 3. INDEX UNTUK POINT BALANCES
        // ============================================
        Schema::table('point_balances', function (Blueprint $table) {
            if (!Schema::hasIndex('point_balances', 'idx_point_balances_user_id')) {
                $table->index('user_id', 'idx_point_balances_user_id');
            }
        });

        // ============================================
        // 4. INDEX UNTUK MEMBERSHIP TIERS
        // ============================================
        Schema::table('membership_tiers', function (Blueprint $table) {
            if (!Schema::hasIndex('membership_tiers', 'idx_membership_tiers_active_min_max')) {
                $table->index(['is_active', 'min_points', 'max_points'], 'idx_membership_tiers_active_min_max');
            }
        });

        // ============================================
        // 5. VERIFIKASI
        // ============================================
        try {
            $explain = DB::select(
                "EXPLAIN SELECT id, name, email, password FROM users WHERE email = 'test@example.com' LIMIT 1"
            );
            \Log::info('✅ Index migration successful!', ['explain' => $explain]);
        } catch (\Exception $e) {
            \Log::warning('⚠️ EXPLAIN query failed', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email_password');
            $table->dropIndex('idx_users_email');
        });

        Schema::table('point_activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_point_activity_logs_user_earned_at');
            $table->dropIndex('idx_point_activity_logs_user_status_expired');
            $table->dropIndex('idx_point_activity_logs_user_activity_code');
        });

        Schema::table('point_balances', function (Blueprint $table) {
            $table->dropIndex('idx_point_balances_user_id');
        });

        Schema::table('membership_tiers', function (Blueprint $table) {
            $table->dropIndex('idx_membership_tiers_active_min_max');
        });
    }
};