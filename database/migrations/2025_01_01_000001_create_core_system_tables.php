<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates core infrastructure tables for Laravel application:
     * - users: Main user management with complete profile data
     * - cache & cache_locks: Laravel cache system
     * - jobs: Queue system for background tasks
     * - sessions: Session management
     * - personal_access_tokens: Sanctum API authentication
     */
    public function up(): void
    {
        // === USERS SYSTEM ===
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Basic Authentication
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Personal Information
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            // Physical Data
            $table->decimal('height', 5, 2)->nullable(); // cm
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->string('blood_type')->nullable();

            // Activity & Preferences
            $table->enum('activity_level', ['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extra_active'])->default('moderately_active');
            $table->json('preferences')->nullable(); // Workout preferences, dietary restrictions, etc.
            $table->json('goals_preferences')->nullable(); // Goal-related preferences

            // System
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['email', 'is_active']);
            $table->index('activity_level');
        });

        // === CACHE SYSTEM ===
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // === QUEUE SYSTEM ===
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // === SESSION MANAGEMENT ===
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // === API AUTHENTICATION (Sanctum) ===
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('users');
    }
};