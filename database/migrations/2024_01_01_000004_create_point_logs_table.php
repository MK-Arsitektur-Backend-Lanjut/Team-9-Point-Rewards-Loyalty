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
        Schema::create('point_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('points_amount');
            $table->string('transaction_type'); // earn, redeem, expire, referral, adjustment
            $table->string('description');
            $table->string('reference_id')->nullable(); // For tracking transaction origins
            $table->json('metadata')->nullable(); // Additional data
            $table->string('status')->default('completed'); // completed, pending, failed
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            // Indexes for optimized queries
            $table->index('user_id');
            $table->index('point_rule_id');
            $table->index('transaction_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_logs');
    }
};
