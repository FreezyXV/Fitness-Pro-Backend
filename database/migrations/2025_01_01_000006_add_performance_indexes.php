<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance indexes for the most common queries in the fitness app
     */
    public function up(): void
    {
        // User-related indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['email', 'email_verified_at'], 'users_email_verified_idx');
                $table->index('activity_level', 'users_activity_level_idx');
                $table->index('created_at', 'users_created_at_idx');
            });
        }

        // Workout indexes for frequent queries
        if (Schema::hasTable('workouts')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->index(['user_id', 'is_template'], 'workouts_user_template_idx');
                $table->index(['is_template', 'category'], 'workouts_template_category_idx');
                $table->index(['is_template', 'difficulty_level'], 'workouts_template_difficulty_idx');
                $table->index(['user_id', 'status', 'completed_at'], 'workouts_user_status_completed_idx');
                $table->index(['status', 'completed_at'], 'workouts_status_completed_idx');
                $table->index(['is_public', 'is_template'], 'workouts_public_template_idx');
                $table->index('created_at', 'workouts_created_at_idx');
            });
        }

        // Workout exercises pivot table indexes
        if (Schema::hasTable('workout_exercises')) {
            Schema::table('workout_exercises', function (Blueprint $table) {
                $table->index(['workout_id', 'order_index'], 'workout_exercises_workout_order_idx');
                $table->index(['exercise_id', 'workout_id'], 'workout_exercises_exercise_workout_idx');
                $table->index(['workout_id', 'is_completed'], 'workout_exercises_workout_completed_idx');
            });
        }

        // Exercise indexes
        if (Schema::hasTable('exercises')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->index('category', 'exercises_category_idx');
                $table->index(['category', 'body_part'], 'exercises_category_bodypart_idx');
                $table->index('difficulty_level', 'exercises_difficulty_idx');
                $table->index(['is_public', 'category'], 'exercises_public_category_idx');

                // Only create fulltext index for MySQL/PostgreSQL, not SQLite
                if (config('database.default') !== 'sqlite') {
                    $table->fullText(['name', 'description'], 'exercises_search_idx');
                }
            });
        }

        // Goals indexes
        if (Schema::hasTable('goals')) {
            Schema::table('goals', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'goals_user_status_idx');
                $table->index(['user_id', 'type'], 'goals_user_type_idx');
                $table->index(['status', 'deadline'], 'goals_status_deadline_idx');
                $table->index('created_at', 'goals_created_at_idx');
            });
        }

        // Calendar tasks indexes (only if table exists)
        if (Schema::hasTable('calendar_tasks')) {
            Schema::table('calendar_tasks', function (Blueprint $table) {
                $table->index(['user_id', 'date'], 'calendar_tasks_user_date_idx');
                $table->index(['user_id', 'type'], 'calendar_tasks_user_type_idx');
                $table->index(['user_id', 'is_completed'], 'calendar_tasks_user_completed_idx');
                $table->index(['date', 'type'], 'calendar_tasks_date_type_idx');
            });
        }

        // User exercise progress indexes (only if table exists)
        if (Schema::hasTable('user_exercise_progress')) {
            Schema::table('user_exercise_progress', function (Blueprint $table) {
                $table->index(['user_id', 'exercise_id'], 'user_exercise_progress_user_exercise_idx');
                $table->index(['user_id', 'recorded_at'], 'user_exercise_progress_user_date_idx');
                $table->index(['exercise_id', 'recorded_at'], 'user_exercise_progress_exercise_date_idx');
            });
        }

        // Nutrition-related indexes
        if (Schema::hasTable('meal_entries')) {
            Schema::table('meal_entries', function (Blueprint $table) {
                $table->index(['user_id', 'date'], 'meal_entries_user_date_idx');
                $table->index(['user_id', 'meal_type'], 'meal_entries_user_type_idx');
                $table->index(['date', 'meal_type'], 'meal_entries_date_type_idx');
            });
        }

        if (Schema::hasTable('water_intake')) {
            Schema::table('water_intake', function (Blueprint $table) {
                $table->index(['user_id', 'date'], 'water_intake_user_date_idx');
                $table->index('date', 'water_intake_date_idx');
            });
        }

        if (Schema::hasTable('nutrition_goals')) {
            Schema::table('nutrition_goals', function (Blueprint $table) {
                $table->index(['user_id', 'is_active'], 'nutrition_goals_user_active_idx');
                $table->index('created_at', 'nutrition_goals_created_at_idx');
            });
        }

        // Achievement system indexes
        if (Schema::hasTable('user_achievements')) {
            Schema::table('user_achievements', function (Blueprint $table) {
                $table->index(['user_id', 'unlocked_at'], 'user_achievements_user_unlocked_idx');
                $table->index(['achievement_id', 'user_id'], 'user_achievements_achievement_user_idx');
            });
        }

        if (Schema::hasTable('user_scores')) {
            Schema::table('user_scores', function (Blueprint $table) {
                $table->index(['user_id', 'updated_at'], 'user_scores_user_updated_idx');
                $table->index(['total_score', 'updated_at'], 'user_scores_score_updated_idx');
            });
        }

        // Sessions and authentication indexes
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index(['user_id', 'last_activity'], 'sessions_user_activity_idx');
                $table->index('last_activity', 'sessions_last_activity_idx');
            });
        }

        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->index(['tokenable_type', 'tokenable_id'], 'personal_access_tokens_tokenable_idx');
                $table->index(['name', 'tokenable_type', 'tokenable_id'], 'personal_access_tokens_name_tokenable_idx');
                $table->index('last_used_at', 'personal_access_tokens_last_used_idx');
                $table->index('expires_at', 'personal_access_tokens_expires_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $tables = [
            'personal_access_tokens' => [
                'personal_access_tokens_tokenable_idx',
                'personal_access_tokens_name_tokenable_idx',
                'personal_access_tokens_last_used_idx',
                'personal_access_tokens_expires_idx'
            ],
            'sessions' => [
                'sessions_user_activity_idx',
                'sessions_last_activity_idx'
            ],
            'user_scores' => [
                'user_scores_user_updated_idx',
                'user_scores_score_updated_idx'
            ],
            'user_achievements' => [
                'user_achievements_user_unlocked_idx',
                'user_achievements_achievement_user_idx'
            ],
            'nutrition_goals' => [
                'nutrition_goals_user_active_idx',
                'nutrition_goals_created_at_idx'
            ],
            'water_intake' => [
                'water_intake_user_date_idx',
                'water_intake_date_idx'
            ],
            'meal_entries' => [
                'meal_entries_user_date_idx',
                'meal_entries_user_type_idx',
                'meal_entries_date_type_idx'
            ],
            'user_exercise_progress' => [
                'user_exercise_progress_user_exercise_idx',
                'user_exercise_progress_user_date_idx',
                'user_exercise_progress_exercise_date_idx'
            ],
            'calendar_tasks' => [
                'calendar_tasks_user_date_idx',
                'calendar_tasks_user_type_idx',
                'calendar_tasks_user_completed_idx',
                'calendar_tasks_date_type_idx'
            ],
            'goals' => [
                'goals_user_status_idx',
                'goals_user_type_idx',
                'goals_status_deadline_idx',
                'goals_created_at_idx'
            ],
            'exercises' => [
                'exercises_category_idx',
                'exercises_category_bodypart_idx',
                'exercises_difficulty_idx',
                'exercises_public_category_idx'
            ],
            'workout_exercises' => [
                'workout_exercises_workout_order_idx',
                'workout_exercises_exercise_workout_idx',
                'workout_exercises_workout_completed_idx'
            ],
            'workouts' => [
                'workouts_user_template_idx',
                'workouts_template_category_idx',
                'workouts_template_difficulty_idx',
                'workouts_user_status_completed_idx',
                'workouts_status_completed_idx',
                'workouts_public_template_idx',
                'workouts_created_at_idx'
            ],
            'users' => [
                'users_email_verified_idx',
                'users_activity_level_idx',
                'users_created_at_idx'
            ]
        ];

        foreach ($tables as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($indexes) {
                    foreach ($indexes as $index) {
                        $tableBlueprint->dropIndex($index);
                    }
                });
            }
        }

        // Handle fulltext index separately for non-SQLite databases
        if (config('database.default') !== 'sqlite' && Schema::hasTable('exercises')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->dropIndex('exercises_search_idx');
            });
        }
    }
};