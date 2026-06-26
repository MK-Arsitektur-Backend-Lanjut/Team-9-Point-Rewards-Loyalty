<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CEK APAKAH TABEL SUDAH ADA
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('membership_tier')->default('bronze');
                $table->string('referral_code')->unique();
                $table->unsignedBigInteger('referred_by_user_id')->nullable();
                $table->decimal('point_multiplier', 3, 2)->default(1.0);
                $table->rememberToken();
                $table->timestamps();

                $table->foreign('referred_by_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } else {
            // Jika tabel sudah ada, tambahkan kolom yang belum ada
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'membership_tier')) {
                    $table->string('membership_tier')->default('bronze');
                }
                if (!Schema::hasColumn('users', 'referral_code')) {
                    $table->string('referral_code')->unique();
                }
                if (!Schema::hasColumn('users', 'referred_by_user_id')) {
                    $table->unsignedBigInteger('referred_by_user_id')->nullable();
                    $table->foreign('referred_by_user_id')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
                if (!Schema::hasColumn('users', 'point_multiplier')) {
                    $table->decimal('point_multiplier', 3, 2)->default(1.0);
                }
            });
        }

        // CEK APAKAH TABEL password_reset_tokens SUDAH ADA
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // CEK APAKAH TABEL sessions SUDAH ADA
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};