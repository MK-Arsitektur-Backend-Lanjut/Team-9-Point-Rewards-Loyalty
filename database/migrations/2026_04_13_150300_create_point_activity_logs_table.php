<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('activity_code');
            $table->unsignedInteger('points_earned');
            $table->json('meta')->nullable();
            $table->timestamp('earned_at');
            $table->timestamps();
            $table->index(['activity_code', 'earned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_activity_logs');
    }
};
