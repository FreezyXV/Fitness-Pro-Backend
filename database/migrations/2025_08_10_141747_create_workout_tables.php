<?php
// 2025_08_10_141747_create_workout_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('difficulty_level')->nullable();
            $table->boolean('is_template')->default(false);
            $table->foreignId('template_id')->nullable()->constrained('workouts')->onDelete('cascade');
            $table->string('status')->default('planned'); // planned, in_progress, completed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('calories_burned')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->integer('order_index')->default(0);
            
            // Planned values
            $table->integer('sets')->nullable();
            $table->integer('reps')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('rest_time_seconds')->nullable();
            $table->decimal('target_weight', 8, 2)->nullable();
            $table->text('notes')->nullable();

            // Actual values
            $table->integer('completed_sets')->nullable();
            $table->integer('completed_reps')->nullable();
            $table->integer('actual_duration_seconds')->nullable();
            $table->decimal('weight_used', 8, 2)->nullable();
            $table->boolean('is_completed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('workouts');
    }
};