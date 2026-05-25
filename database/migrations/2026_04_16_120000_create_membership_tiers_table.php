<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('min_points')->default(0);
            $table->unsignedInteger('max_points')->nullable();
            $table->decimal('point_multiplier', 5, 2)->default(1.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'min_points', 'max_points']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_tiers');
    }
};
