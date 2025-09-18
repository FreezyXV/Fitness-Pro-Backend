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
     * This migration adds the missing actual_duration and actual_calories columns
     * that are essential for workout completion functionality.
     */
    public function up(): void
    {
        // Add missing actual_duration column if it doesn't exist
        if (!Schema::hasColumn('workouts', 'actual_duration')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->integer('actual_duration')->nullable()->after('completed_at');
            });
        }

        // Add missing actual_calories column if it doesn't exist
        if (!Schema::hasColumn('workouts', 'actual_calories')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->integer('actual_calories')->nullable()->after('actual_duration');
            });
        }

        // Update existing completed workouts with default values if they're null
        // This prevents issues with existing data (using completed_at to identify completed workouts)
        DB::statement('UPDATE workouts SET actual_duration = estimated_duration WHERE actual_duration IS NULL AND estimated_duration IS NOT NULL AND completed_at IS NOT NULL');
        DB::statement('UPDATE workouts SET actual_calories = estimated_calories WHERE actual_calories IS NULL AND estimated_calories IS NOT NULL AND completed_at IS NOT NULL');

        // For completed workouts without any duration data, set a reasonable default
        DB::statement('UPDATE workouts SET actual_duration = 30 WHERE actual_duration IS NULL AND completed_at IS NOT NULL');
        DB::statement('UPDATE workouts SET actual_calories = 150 WHERE actual_calories IS NULL AND completed_at IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('workouts', 'actual_calories')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->dropColumn('actual_calories');
            });
        }

        if (Schema::hasColumn('workouts', 'actual_duration')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->dropColumn('actual_duration');
            });
        }
    }
};