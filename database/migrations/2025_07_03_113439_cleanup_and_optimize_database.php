<?php
//migrations/2025_07_03_113439_cleanup_and_optimize_database.php
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
        // === 1. USERS TABLE ===
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Profil utilisateur
            $table->integer('age')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('profile_photo_url')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('location')->nullable();
            $table->text('bio')->nullable();
            $table->enum('activity_level', ['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extremely_active'])->nullable();
            $table->json('goals')->nullable();
            $table->json('preferences')->nullable();

            $table->index(['email'], 'idx_users_email');
        });

        // === 2. EXERCISES TABLE ===
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('body_part');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->integer('duration')->nullable();
            $table->json('muscle_groups')->nullable();
            $table->string('equipment_needed')->nullable();
            $table->string('video_url')->nullable();
            $table->json('instructions')->nullable();
            $table->json('tips')->nullable();
            $table->string('category')->nullable();
            $table->integer('estimated_calories_per_minute')->nullable();
            $table->timestamps();

            $table->index(['body_part', 'difficulty']);
            $table->index(['category']);
        });

        // === 3. WORKOUT PLANS ===
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('exercises');
            $table->integer('estimated_duration');
            $table->integer('estimated_calories')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('category', ['strength', 'cardio', 'flexibility', 'hiit'])->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_custom')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'difficulty_level']);
            $table->index(['category']);
        });

        // === 4. WORKOUT SESSIONS ===
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workout_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->integer('duration_minutes');
            $table->integer('calories_burned')->nullable();
            $table->json('completed_exercises')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['in_progress', 'completed', 'paused', 'cancelled'])->default('completed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['workout_plan_id']);
            $table->index(['user_id', 'completed_at'], 'idx_sessions_user_date');
            $table->index(['user_id', 'duration_minutes', 'calories_burned'], 'idx_sessions_performance');
        });

        // === 5. GOALS ===
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_value', 10, 2);
            $table->decimal('current_value', 10, 2)->default(0);
            $table->string('unit', 50);
            $table->date('target_date');
            $table->enum('status', ['active', 'completed', 'paused'])->default('active');
            $table->string('category')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'target_date']);
            $table->index(['category']);
            $table->index(['user_id', 'status', 'target_date'], 'idx_goals_progress');
        });

        // === 6. CALENDAR TASKS ===
        Schema::create('calendar_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('task_date');
            $table->enum('task_type', ['workout', 'goal', 'reminder', 'nutrition', 'rest']);
            $table->boolean('is_completed')->default(false);
            $table->foreignId('workout_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('reminder_time')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamps();

            $table->index(['user_id', 'task_date']);
            $table->index(['user_id', 'task_type']);
            $table->index(['user_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_tasks');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('workout_sessions');
        Schema::dropIfExists('workout_plans');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('users');
    }
};