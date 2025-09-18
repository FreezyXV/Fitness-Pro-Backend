<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds missing columns to the workouts table that are referenced
     * in the Workout model but don't exist in the database schema.
     */
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            // Add difficulty column (model expects this, but table has difficulty_level)
            if (!Schema::hasColumn('workouts', 'difficulty')) {
                $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('description');
            }

            // Add body_focus column (referenced in model logic)
            if (!Schema::hasColumn('workouts', 'body_focus')) {
                $table->string('body_focus')->nullable()->after('difficulty');
            }

            // Add type column (referenced in model logic)
            if (!Schema::hasColumn('workouts', 'type')) {
                $table->string('type')->nullable()->after('body_focus');
            }

            // Add intensity column (referenced in model logic)
            if (!Schema::hasColumn('workouts', 'intensity')) {
                $table->enum('intensity', ['low', 'medium', 'high'])->default('medium')->after('type');
            }

            // Add equipment column (referenced in model logic)
            if (!Schema::hasColumn('workouts', 'equipment')) {
                $table->string('equipment')->nullable()->after('intensity');
            }

            // Add goal column (referenced in model logic)
            if (!Schema::hasColumn('workouts', 'goal')) {
                $table->string('goal')->nullable()->after('equipment');
            }

            // Add frequency column (referenced in model logic)
            if (!Schema::hasColumn('workouts', 'frequency')) {
                $table->string('frequency')->nullable()->after('goal');
            }
        });

        // Update existing data for consistency - sync with existing columns
        try {
            // Sync difficulty with difficulty_level if it exists
            if (Schema::hasColumn('workouts', 'difficulty_level')) {
                DB::statement("UPDATE workouts SET difficulty = difficulty_level WHERE (difficulty IS NULL OR difficulty = '') AND difficulty_level IS NOT NULL");
            }

            // Sync type with category if it exists
            if (Schema::hasColumn('workouts', 'category')) {
                DB::statement("UPDATE workouts SET type = category WHERE (type IS NULL OR type = '') AND category IS NOT NULL");
            }

            // Set default values for new columns where needed
            DB::statement("UPDATE workouts SET body_focus = 'full_body' WHERE body_focus IS NULL");
            DB::statement("UPDATE workouts SET equipment = 'none' WHERE equipment IS NULL");
            DB::statement("UPDATE workouts SET goal = 'fitness' WHERE goal IS NULL");
            DB::statement("UPDATE workouts SET frequency = 'weekly' WHERE frequency IS NULL");

        } catch (\Exception $e) {
            // Log error but don't fail migration
            \Log::warning('Error updating workout data during migration: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $columns = ['frequency', 'goal', 'equipment', 'intensity', 'type', 'body_focus', 'difficulty'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('workouts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};