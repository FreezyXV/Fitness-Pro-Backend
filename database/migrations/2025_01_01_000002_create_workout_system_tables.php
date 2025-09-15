<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates workout and exercise system tables:
     * - exercises: Complete exercise database with metadata
     * - workouts: Unified workout system (templates + instances)
     * - workout_exercises: Detailed exercise tracking within workouts
     */
    public function up(): void
    {
        // === EXERCISE DATABASE ===
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name')->unique();
            $table->text('description')->nullable();

            // Exercise Classification
            $table->string('body_part'); // chest, back, legs, arms, shoulders, abs, cardio, mobility, flexibility
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->string('category')->nullable(); // strength, cardio, flexibility, mobility, hiit

            // Exercise Metadata
            $table->integer('duration')->nullable(); // Duration in minutes for time-based exercises
            $table->string('equipment')->nullable(); // Required equipment
            $table->integer('estimated_calories')->nullable(); // Calories per session/rep

            // Exercise Data (JSON for flexibility)
            $table->json('muscle_groups')->nullable(); // Primary and secondary muscle groups
            $table->json('instructions')->nullable(); // Step-by-step instructions
            $table->json('tips')->nullable(); // Form tips and advice

            // Media
            $table->string('image')->nullable();
            $table->string('video_url')->nullable();
            $table->string('gif_url')->nullable();

            // System
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['body_part', 'difficulty']);
            $table->index(['category', 'is_active']);
            $table->index('equipment');
        });

        // === WORKOUT SYSTEM ===
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Template System
            $table->boolean('is_template')->default(false);
            $table->foreignId('template_id')->nullable()->constrained('workouts')->onDelete('set null');

            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();

            // Workout Classification
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->string('type')->nullable(); // strength, cardio, hiit, flexibility, mobility
            $table->string('focus')->nullable(); // full_body, upper_body, lower_body, specific_muscle
            $table->enum('intensity', ['low', 'medium', 'high'])->default('medium');

            // Workout Planning
            $table->integer('estimated_duration')->nullable(); // minutes
            $table->integer('estimated_calories')->nullable();
            $table->string('equipment')->nullable();
            $table->string('target_goal')->nullable(); // strength, endurance, weight_loss, muscle_gain
            $table->integer('recommended_frequency')->nullable(); // times per week

            // Workout Status & Tracking
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('actual_duration')->nullable(); // actual time spent
            $table->integer('actual_calories')->nullable(); // calories burned

            // Performance Tracking
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('difficulty_felt', ['too_easy', 'just_right', 'too_hard'])->nullable();
            $table->integer('effort_level')->nullable(); // 1-10 scale
            $table->text('notes')->nullable();

            // System
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_template']);
            $table->index(['user_id', 'status']);
            $table->index(['template_id', 'created_at']);
            $table->index(['type', 'difficulty']);
            $table->index('completed_at');
        });

        // === WORKOUT-EXERCISE RELATIONSHIP ===
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');

            // Exercise Order & Organization
            $table->integer('order')->default(1); // Order within the workout
            $table->string('superset_group')->nullable(); // For grouping exercises in supersets

            // Planned Values (from template or plan)
            $table->integer('planned_sets')->nullable();
            $table->integer('planned_reps')->nullable();
            $table->decimal('planned_weight', 8, 2)->nullable(); // kg
            $table->integer('planned_duration')->nullable(); // seconds
            $table->integer('planned_rest')->nullable(); // seconds between sets

            // Actual Values (performed during workout)
            $table->integer('actual_sets')->nullable();
            $table->integer('actual_reps')->nullable();
            $table->decimal('actual_weight', 8, 2)->nullable(); // kg
            $table->integer('actual_duration')->nullable(); // seconds
            $table->integer('actual_rest')->nullable(); // seconds

            // Performance Tracking
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('difficulty_felt', ['too_easy', 'just_right', 'too_hard'])->nullable();
            $table->integer('effort_level')->nullable(); // 1-10 RPE scale
            $table->boolean('is_personal_record')->default(false);
            $table->decimal('one_rep_max', 8, 2)->nullable(); // Calculated 1RM

            // Form & Quality
            $table->enum('form_quality', ['poor', 'good', 'excellent'])->nullable();
            $table->text('notes')->nullable();

            // System
            $table->timestamps();

            // Indexes for performance queries
            $table->index(['workout_id', 'order']);
            $table->index(['exercise_id', 'is_personal_record']);
            $table->index(['workout_id', 'completion_percentage']);

            // Ensure unique combination within a workout
            $table->unique(['workout_id', 'exercise_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('workouts');
        Schema::dropIfExists('exercises');
    }
};