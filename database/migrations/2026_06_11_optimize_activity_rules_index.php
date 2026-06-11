<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_rules', function (Blueprint $table) {
            // Add composite index for faster lookups
            // Greatly improves query: WHERE activity_code = ? AND is_active = 1
            if (!Schema::hasIndex('activity_rules', 'idx_activity_code_active')) {
                $table->index(['activity_code', 'is_active'], 'idx_activity_code_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_rules', function (Blueprint $table) {
            $table->dropIndex('idx_activity_code_active');
        });
    }
};
