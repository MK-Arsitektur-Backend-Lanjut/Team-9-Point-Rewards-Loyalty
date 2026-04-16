<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('referral_code', 64);
            $table->unsignedInteger('referrer_bonus_points')->default(0);
            $table->unsignedInteger('referee_bonus_points')->default(0);
            $table->timestamp('rewarded_at');
            $table->timestamps();

            $table->index(['referrer_user_id', 'rewarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_logs');
    }
};
