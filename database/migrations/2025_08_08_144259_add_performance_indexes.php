<?php
// database/migrations/2024_XX_XX_add_performance_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing indexes for better query performance
        
        // Workout sessions optimizations
        try {
            Schema::table('workout_sessions', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'completed_at'], 'idx_sessions_user_status_date');
                $table->index(['user_id', 'duration_minutes', 'calories_burned'], 'idx_sessions_performance');
                $table->index(['user_id', 'created_at'], 'idx_sessions_user_created');
            });
        } catch (\Exception $e) {}

        // Goals optimizations
        try {
            Schema::table('goals', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'target_date'], 'idx_goals_user_status_date');
                $table->index(['user_id', 'status', 'current_value'], 'idx_goals_progress');
            });
        } catch (\Exception $e) {}

        // Calendar tasks optimizations
        try {
            Schema::table('calendar_tasks', function (Blueprint $table) {
                $table->index(['user_id', 'task_date', 'is_completed'], 'idx_tasks_user_date_status');
                $table->index(['user_id', 'task_type', 'task_date'], 'idx_tasks_type_date');
                $table->index(['user_id', 'priority'], 'idx_tasks_priority');
            });
        } catch (\Exception $e) {}

        // Workout plans optimizations
        try {
            Schema::table('workout_plans', function (Blueprint $table) {
                $table->index(['category', 'difficulty_level'], 'idx_plans_category_difficulty');
                $table->index(['user_id', 'is_custom'], 'idx_plans_user_custom');
            });
        } catch (\Exception $e) {}

        // Exercises optimizations (if not already present)
        try {
            Schema::table('exercises', function (Blueprint $table) {
                $table->index(['name'], 'idx_exercises_name');
                $table->index(['body_part', 'difficulty', 'category'], 'idx_exercises_filtering');
            });
        } catch (\Exception $e) {}

        // Users table optimizations
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['email_verified_at'], 'idx_users_verified');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_sessions_user_status_date');
        DB::statement('DROP INDEX IF EXISTS idx_sessions_performance');
        DB::statement('DROP INDEX IF EXISTS idx_sessions_user_created');

        DB::statement('DROP INDEX IF EXISTS idx_goals_user_status_date');
        DB::statement('DROP INDEX IF EXISTS idx_goals_progress');

        DB::statement('DROP INDEX IF EXISTS idx_tasks_user_date_status');
        DB::statement('DROP INDEX IF EXISTS idx_tasks_type_date');
        DB::statement('DROP INDEX IF EXISTS idx_tasks_priority');

        DB::statement('DROP INDEX IF EXISTS idx_plans_category_difficulty');
        DB::statement('DROP INDEX IF EXISTS idx_plans_user_custom');

        DB::statement('DROP INDEX IF EXISTS idx_exercises_name');
        DB::statement('DROP INDEX IF EXISTS idx_exercises_filtering');

        DB::statement('DROP INDEX IF EXISTS idx_users_verified');
    }
};