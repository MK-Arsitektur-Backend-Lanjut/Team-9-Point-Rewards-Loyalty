<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'earned_at'], 'idx_point_activity_logs_user_earned_at');
            $table->index(['user_id', 'point_status', 'expired_at'], 'idx_point_activity_logs_user_status_expired');
            $table->index(['user_id', 'activity_code'], 'idx_point_activity_logs_user_activity_code');
        });
    }

    public function down(): void
    {
        Schema::table('point_activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_point_activity_logs_user_earned_at');
            $table->dropIndex('idx_point_activity_logs_user_status_expired');
            $table->dropIndex('idx_point_activity_logs_user_activity_code');
        });
    }
};
