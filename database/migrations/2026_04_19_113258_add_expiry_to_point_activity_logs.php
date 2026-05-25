<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('point_activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('point_activity_logs', 'expired at')) {
                $table->timestamp('expired_at')
                    ->nullable()
                    ->after('earned_at')
                    ->comment('Tanggal kadaluarsa poin (1 tahun setelah earned_at)');
            }

            if(!Schema::hasColumn('point_activity_logs', 'point_status')) {
                $table->enum('point_status', ['active', 'expired', 'redeemed'])
                    ->default('active')
                    ->after('points_earned')
                    ->comment('Status poin: active=masih berlaku, expired=sudah kadaluarsa, redeemed=sudah digunakan');
            }
        });

        DB::table('point_activity_logs')
            ->whereNull('expired_at')
            ->where('points_earned', '>', 0)
            ->update([
                'expired_at' => DB::raw('DATE_ADD(earned_at, INTERVAL 1 YEAR)'),
                'point_status' => 'active',
            ]);

        DB::table('point_activity_logs')
            ->where('points_earned', '<', 0)
            ->whereNull('point_status')
            ->update([
                'point_status' => 'redeemed',
            ]);
    }

    /**
     * Roll back the migrations.
     */
    public function down(): void
    {
        Schema::table('point_activity_logs', function (Blueprint $table) {
            $table->dropColumn('expired_at');
            $table->dropColumn('point_status');
        });
    }
};
