<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates performance optimizations and indexes for the fitness application:
     * - Comprehensive indexes for all main tables
     * - Foreign key optimizations
     * - Query performance improvements
     * - Search and filtering optimizations
     */
    public function up(): void
    {
        // === USER SYSTEM OPTIMIZATIONS ===
        Schema::table('users', function (Blueprint $table) {
            // Additional performance indexes
            $table->index(['created_at', 'is_active']);
            $table->index(['activity_level', 'gender']);
            $table->index(['birth_date', 'is_active']); // Age-based queries
        });

        // === EXERCISE SYSTEM OPTIMIZATIONS ===
        Schema::table('exercises', function (Blueprint $table) {
            // Compound indexes for common queries
            $table->index(['body_part', 'difficulty', 'is_active']);
            $table->index(['category', 'equipment', 'difficulty']);
            $table->index(['estimated_calories', 'duration']);

            // Full-text search optimization (if needed)
            $table->index('name'); // Already created in main migration
        });

        // === WORKOUT SYSTEM OPTIMIZATIONS ===
        Schema::table('workouts', function (Blueprint $table) {
            // Performance tracking queries
            $table->index(['user_id', 'completed_at', 'status']);
            $table->index(['type', 'difficulty', 'is_template']);
            $table->index(['template_id', 'status', 'created_at']);

            // Analytics and reporting
            $table->index(['created_at', 'completed_at']);
            $table->index(['estimated_duration', 'actual_duration']);
            $table->index(['focus', 'intensity', 'type']);
        });

        Schema::table('workout_exercises', function (Blueprint $table) {
            // Performance and progress queries
            $table->index(['exercise_id', 'weight_used', 'created_at']);
            $table->index(['workout_id', 'is_personal_record']);
            $table->index(['exercise_id', 'is_personal_record', 'weight_used']);

            // Progress tracking
            $table->index(['exercise_id', 'completion_percentage', 'created_at']);
            $table->index(['workout_id', 'effort_level', 'difficulty_felt']);
        });

        // === NUTRITION SYSTEM OPTIMIZATIONS ===
        Schema::table('aliments', function (Blueprint $table) {
            // Search and filtering
            $table->index(['calories', 'proteins', 'carbohydrates']);
            $table->index(['category', 'is_verified', 'is_active']);

            // Nutritional content queries
            $table->index(['fiber', 'sugar']); // Dietary preferences
            $table->index(['sodium', 'potassium']); // Health tracking
        });

        Schema::table('meal_entries', function (Blueprint $table) {
            // Daily nutrition tracking
            $table->index(['user_id', 'date', 'meal_type']);
            $table->index(['date', 'calories', 'user_id']);

            // Nutritional analysis
            $table->index(['user_id', 'proteins', 'date']);
            $table->index(['user_id', 'carbohydrates', 'date']);
            $table->index(['aliment_id', 'quantity', 'date']);
        });

        Schema::table('water_intakes', function (Blueprint $table) {
            // Daily hydration tracking
            $table->index(['user_id', 'date', 'amount']);
            $table->index(['date', 'source', 'amount']);
        });

        Schema::table('user_diets', function (Blueprint $table) {
            // Diet tracking and analysis
            $table->index(['user_id', 'status', 'start_date']);
            $table->index(['type', 'category', 'status']);
            $table->index(['compliance_score', 'current_streak']);
            $table->index(['start_date', 'end_date', 'status']);
        });

        // === GAMIFICATION SYSTEM OPTIMIZATIONS ===
        Schema::table('goals', function (Blueprint $table) {
            // Goal progress and completion queries
            $table->index(['user_id', 'completion_percentage', 'status']);
            $table->index(['category', 'target_date', 'status']);
            $table->index(['priority', 'target_date', 'status']);

            // Progress tracking
            $table->index(['last_progress_update', 'status']);
            $table->index(['target_value', 'current_value', 'unit']);
        });

        Schema::table('user_scores', function (Blueprint $table) {
            // Leaderboard and ranking queries
            $table->index(['total_points', 'level', 'current_streak']);
            $table->index(['achievements_unlocked', 'goals_completed']);
            $table->index(['best_streak', 'current_streak']);

            // Time-based statistics
            $table->index(['weekly_goals_completed', 'monthly_goals_completed']);
            $table->index(['streak_last_updated', 'current_streak']);
        });

        Schema::table('achievements', function (Blueprint $table) {
            // Achievement browsing and filtering
            $table->index(['category', 'rarity', 'is_active']);
            $table->index(['points', 'rarity']);
            $table->index(['sort_order', 'category', 'is_active']);
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            // Achievement progress and history
            $table->index(['user_id', 'points_earned', 'unlocked_at']);
            $table->index(['achievement_id', 'points_earned']);
            $table->index(['unlocked_at', 'points_earned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Laravel automatically drops indexes when columns are dropped
        // Manual index drops are not typically needed for rollbacks
        // But if needed, they would be dropped in reverse order here

        // The table drops in the previous migrations will handle index cleanup
    }
};