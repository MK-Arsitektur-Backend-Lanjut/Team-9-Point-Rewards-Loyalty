<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // CEK APAKAH KOLOM SUDAH ADA SEBELUM MENAMBAH
            if (!Schema::hasColumn('users', 'membership_tier_id')) {
                $table->foreignId('membership_tier_id')->nullable()->after('points')->constrained('membership_tiers')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 64)->nullable()->unique()->after('membership_tier_id');
            }
            
            if (!Schema::hasColumn('users', 'referred_by_user_id')) {
                $table->foreignId('referred_by_user_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropUnique('users_referral_code_unique');
            $table->dropColumn('referral_code');
            $table->dropConstrainedForeignId('membership_tier_id');
        });
    }
};