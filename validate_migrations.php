<?php

/**
 * Migration Validation Script
 *
 * This script validates that all indexes in the performance optimization migration
 * reference columns that actually exist in their respective tables.
 */

// Define table schemas based on migration files
$tableSchemas = [
    'users' => [
        'id', 'name', 'email', 'email_verified_at', 'password', 'remember_token',
        'first_name', 'last_name', 'phone', 'bio', 'avatar', 'birth_date', 'gender',
        'height', 'weight', 'blood_type',
        'activity_level', 'preferences', 'goals_preferences',
        'is_active', 'created_at', 'updated_at'
    ],
    'exercises' => [
        'id', 'name', 'description', 'category', 'body_part', 'equipment',
        'target_muscles', 'secondary_muscles', 'instructions',
        'difficulty', 'estimated_calories', 'duration', 'sets', 'reps',
        'video_url', 'image_url', 'gif_url',
        'is_active', 'created_at', 'updated_at'
    ],
    'workouts' => [
        'id', 'user_id', 'is_template', 'template_id',
        'name', 'description', 'difficulty', 'type', 'focus', 'intensity',
        'estimated_duration', 'estimated_calories', 'equipment', 'target_goal', 'recommended_frequency',
        'status', 'started_at', 'completed_at', 'actual_duration', 'actual_calories',
        'completion_percentage', 'difficulty_felt', 'effort_level', 'notes',
        'created_at', 'updated_at'
    ],
    'workout_exercises' => [
        'id', 'workout_id', 'exercise_id', 'order_index', 'superset_group',
        'sets', 'reps', 'target_weight', 'duration_seconds', 'rest_time_seconds',
        'completed_sets', 'completed_reps', 'weight_used', 'actual_duration_seconds', 'notes',
        'completion_percentage', 'difficulty_felt', 'effort_level', 'is_personal_record', 'one_rep_max', 'is_completed',
        'form_quality', 'created_at', 'updated_at'
    ],
    'aliments' => [
        'id', 'name', 'brand', 'category',
        'calories', 'proteins', 'carbohydrates', 'fats', 'fiber', 'sugar', 'sodium', 'potassium', 'vitamin_c',
        'unit', 'serving_size', 'barcode', 'image_url',
        'is_verified', 'is_active', 'created_at', 'updated_at'
    ],
    'meal_entries' => [
        'id', 'user_id', 'aliment_id', 'date', 'meal_type', 'quantity', 'unit',
        'calories', 'proteins', 'carbohydrates', 'fats', 'fiber', 'sugar', 'sodium', 'potassium', 'vitamin_c',
        'notes', 'time', 'created_at', 'updated_at'
    ],
    'water_intakes' => [
        'id', 'user_id', 'date', 'amount', 'time', 'source',
        'created_at', 'updated_at'
    ],
    'user_diets' => [
        'id', 'user_id', 'name', 'description', 'type', 'category',
        'start_date', 'end_date', 'status',
        'compliance_score', 'current_streak', 'best_streak', 'last_tracked_date',
        'notes', 'created_at', 'updated_at'
    ],
    'goals' => [
        'id', 'user_id', 'name', 'description', 'category', 'type',
        'target_value', 'current_value', 'unit', 'target_date',
        'status', 'priority', 'completion_percentage', 'last_progress_update',
        'notes', 'created_at', 'updated_at'
    ],
    'user_scores' => [
        'id', 'user_id',
        'total_points', 'level', 'current_streak', 'best_streak',
        'achievements_unlocked', 'goals_completed',
        'weekly_goals_completed', 'monthly_goals_completed',
        'streak_last_updated',
        'created_at', 'updated_at'
    ],
    'achievements' => [
        'id', 'name', 'description', 'category', 'rarity',
        'points', 'icon', 'criteria', 'sort_order',
        'is_active', 'created_at', 'updated_at'
    ],
    'user_achievements' => [
        'id', 'user_id', 'achievement_id',
        'unlocked_at', 'points_earned',
        'created_at', 'updated_at'
    ],
];

// Define indexes from performance optimization migration
$indexes = [
    'users' => [
        ['created_at', 'is_active'],
        ['activity_level', 'gender'],
        ['birth_date', 'is_active'],
    ],
    'exercises' => [
        ['body_part', 'difficulty', 'is_active'],
        ['category', 'equipment', 'difficulty'],
        ['estimated_calories', 'duration'],
        ['name'],
    ],
    'workouts' => [
        ['user_id', 'completed_at', 'status'],
        ['type', 'difficulty', 'is_template'],
        ['template_id', 'status', 'created_at'],
        ['created_at', 'completed_at'],
        ['estimated_duration', 'actual_duration'],
        ['focus', 'intensity', 'type'],
    ],
    'workout_exercises' => [
        ['exercise_id', 'weight_used', 'created_at'],
        ['workout_id', 'is_personal_record'],
        ['exercise_id', 'is_personal_record', 'weight_used'],
        ['exercise_id', 'completion_percentage', 'created_at'],
        ['workout_id', 'effort_level', 'difficulty_felt'],
    ],
    'aliments' => [
        ['calories', 'proteins', 'carbohydrates'],
        ['category', 'is_verified', 'is_active'],
        ['fiber', 'sugar'],
        ['sodium', 'potassium'],
    ],
    'meal_entries' => [
        ['user_id', 'date', 'meal_type'],
        ['date', 'calories', 'user_id'],
        ['user_id', 'proteins', 'date'],
        ['user_id', 'carbohydrates', 'date'],
        ['aliment_id', 'quantity', 'date'],
    ],
    'water_intakes' => [
        ['user_id', 'date', 'amount'],
        ['date', 'source', 'amount'],
    ],
    'user_diets' => [
        ['user_id', 'status', 'start_date'],
        ['type', 'category', 'status'],
        ['compliance_score', 'current_streak'],
        ['start_date', 'end_date', 'status'],
    ],
    'goals' => [
        ['user_id', 'completion_percentage', 'status'],
        ['category', 'target_date', 'status'],
        ['priority', 'target_date', 'status'],
        ['last_progress_update', 'status'],
        ['target_value', 'current_value', 'unit'],
    ],
    'user_scores' => [
        ['total_points', 'level', 'current_streak'],
        ['achievements_unlocked', 'goals_completed'],
        ['best_streak', 'current_streak'],
        ['weekly_goals_completed', 'monthly_goals_completed'],
        ['streak_last_updated', 'current_streak'],
    ],
    'achievements' => [
        ['category', 'rarity', 'is_active'],
        ['points', 'rarity'],
        ['sort_order', 'category', 'is_active'],
    ],
    'user_achievements' => [
        ['user_id', 'points_earned', 'unlocked_at'],
        ['achievement_id', 'points_earned'],
        ['unlocked_at', 'points_earned'],
    ],
];

// Validate indexes
$errors = [];
$warnings = [];
$totalIndexes = 0;
$validIndexes = 0;

foreach ($indexes as $table => $tableIndexes) {
    if (!isset($tableSchemas[$table])) {
        $errors[] = "Table '$table' not found in schema definitions";
        continue;
    }

    $schema = $tableSchemas[$table];

    foreach ($tableIndexes as $index) {
        $totalIndexes++;
        $indexColumns = is_array($index) ? $index : [$index];
        $indexValid = true;

        foreach ($indexColumns as $column) {
            if (!in_array($column, $schema)) {
                $errors[] = "Column '$column' in index for table '$table' does not exist!";
                $errors[] = "  Available columns: " . implode(', ', $schema);
                $indexValid = false;
            }
        }

        if ($indexValid) {
            $validIndexes++;
        }
    }
}

// Print results
echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║         MIGRATION VALIDATION REPORT                       ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "Total Indexes Checked: $totalIndexes\n";
echo "Valid Indexes: $validIndexes\n";
echo "Invalid Indexes: " . ($totalIndexes - $validIndexes) . "\n\n";

if (empty($errors)) {
    echo "✅ All indexes are valid!\n";
    echo "✅ All column references exist in their respective tables.\n";
    echo "✅ Migration 2025_01_01_000005 is ready to run.\n\n";
} else {
    echo "❌ ERRORS FOUND:\n\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNINGS:\n\n";
    foreach ($warnings as $warning) {
        echo "  $warning\n";
    }
    echo "\n";
}

exit(empty($errors) ? 0 : 1);
