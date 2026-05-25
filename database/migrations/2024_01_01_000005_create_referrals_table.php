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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referred_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('referral_code');
            $table->bigInteger('points_awarded')->default(0);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            // Indexes
            $table->index('referred_by_user_id');
            $table->index('referred_user_id');
            $table->index('referral_code');
            $table->index('created_at');
            $table->unique(['referred_by_user_id', 'referred_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
