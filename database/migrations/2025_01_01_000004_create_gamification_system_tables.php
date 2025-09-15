<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates gamification and achievement system:
     * - goals: General goal tracking system
     * - user_scores: User progression and scoring
     * - achievements: Achievement definitions and requirements
     * - user_achievements: User-achieved accomplishments
     */
    public function up(): void
    {
        // === GOAL SYSTEM ===
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Goal Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // weight, cardio, strength, flexibility, mental, nutrition

            // Goal Tracking
            $table->decimal('target_value', 10, 2);
            $table->decimal('current_value', 10, 2)->default(0);
            $table->string('unit'); // kg, km, reps, days, points, etc.
            $table->date('target_date')->nullable();

            // Goal Status
            $table->enum('status', ['not-started', 'active', 'completed', 'paused'])->default('not-started');
            $table->integer('priority')->default(3); // 1-5 scale (1=highest)

            // Progress Tracking
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->timestamp('last_progress_update')->nullable();

            // System
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['category', 'status']);
            $table->index(['target_date', 'status']);
            $table->index(['priority', 'status']);

            // Note: SQLite enum constraints are handled by the enum definition above
        });

        // === USER SCORING SYSTEM ===
        Schema::create('user_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Point System
            $table->integer('total_points')->default(0);
            $table->integer('level')->default(1);
            $table->integer('level_progress')->default(0); // Points towards next level

            // Streak System
            $table->integer('current_streak')->default(0);
            $table->integer('best_streak')->default(0);
            $table->date('streak_last_updated')->nullable();

            // Goal Statistics
            $table->integer('goals_completed')->default(0);
            $table->integer('goals_created')->default(0);
            $table->integer('weekly_goals_completed')->default(0);
            $table->integer('monthly_goals_completed')->default(0);

            // Achievement Statistics
            $table->integer('achievements_unlocked')->default(0);

            // Milestone Data (flexible JSON structure for future expansions)
            $table->json('milestone_data')->nullable();

            // System
            $table->timestamps();

            // One score record per user
            $table->unique('user_id');

            // Indexes for leaderboards
            $table->index(['total_points', 'level']);
            $table->index('current_streak');
            $table->index('goals_completed');
        });

        // === ACHIEVEMENT DEFINITIONS ===
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();

            // Achievement Identity
            $table->string('key')->unique(); // Unique identifier for code references
            $table->string('name');
            $table->text('description');
            $table->string('icon')->nullable(); // Icon class or image path

            // Achievement Properties
            $table->integer('points')->default(10); // Points awarded when unlocked
            $table->enum('category', ['goals', 'streak', 'progress', 'milestone', 'special'])->default('goals');
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');

            // Achievement Requirements (flexible JSON structure)
            $table->json('requirements')->nullable(); // Conditions to unlock

            // System
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['category', 'is_active']);
            $table->index(['rarity', 'is_active']);
            $table->index('sort_order');
        });

        // === USER ACHIEVEMENTS ===
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained()->onDelete('cascade');

            // Achievement Progress
            $table->json('progress_data')->nullable(); // Detailed progress information
            $table->integer('points_earned')->default(0); // Points awarded (may vary)
            $table->timestamp('unlocked_at');

            // System
            $table->timestamps();

            // Prevent duplicate achievements per user
            $table->unique(['user_id', 'achievement_id']);

            // Indexes
            $table->index(['user_id', 'unlocked_at']);
            $table->index(['achievement_id', 'unlocked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('user_scores');
        Schema::dropIfExists('goals');
    }
};