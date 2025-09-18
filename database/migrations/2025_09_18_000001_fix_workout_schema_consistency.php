<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix workouts table - add missing columns and ensure consistency
        Schema::table('workouts', function (Blueprint $table) {
            // Add difficulty_level if it doesn't exist (maps to difficulty)
            if (!Schema::hasColumn('workouts', 'difficulty_level')) {
                $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('difficulty');
            }

            // Add category if it doesn't exist (maps to type)
            if (!Schema::hasColumn('workouts', 'category')) {
                $table->string('category')->nullable()->after('type');
            }

            // Add estimated fields if they don't exist
            if (!Schema::hasColumn('workouts', 'estimated_duration')) {
                $table->integer('estimated_duration')->nullable()->after('actual_calories');
            }

            if (!Schema::hasColumn('workouts', 'estimated_calories')) {
                $table->integer('estimated_calories')->nullable()->after('estimated_duration');
            }
        });

        // Update existing data to maintain consistency
        DB::statement('UPDATE workouts SET difficulty_level = difficulty WHERE difficulty_level IS NULL OR difficulty_level = ""');
        DB::statement('UPDATE workouts SET category = type WHERE category IS NULL OR category = ""');
        DB::statement('UPDATE workouts SET estimated_duration = actual_duration WHERE estimated_duration IS NULL AND actual_duration IS NOT NULL');
        DB::statement('UPDATE workouts SET estimated_calories = actual_calories WHERE estimated_calories IS NULL AND actual_calories IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            if (Schema::hasColumn('workouts', 'difficulty_level')) {
                $table->dropColumn('difficulty_level');
            }

            if (Schema::hasColumn('workouts', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('workouts', 'estimated_duration')) {
                $table->dropColumn('estimated_duration');
            }

            if (Schema::hasColumn('workouts', 'estimated_calories')) {
                $table->dropColumn('estimated_calories');
            }
        });
    }
};