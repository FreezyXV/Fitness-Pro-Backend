<?php
// 2025_08_10_173334_create_workout_exercises_table.php
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
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            
            // Template/Plan specific fields
            $table->integer('order_index')->default(0);
            $table->integer('sets')->nullable();
            $table->integer('reps')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('rest_time_seconds')->default(60);
            $table->decimal('target_weight', 8, 2)->nullable();
            $table->text('notes')->nullable();
            
            // Session specific fields (for actual workout execution)
            $table->integer('completed_sets')->default(0);
            $table->integer('completed_reps')->default(0);
            $table->integer('actual_duration_seconds')->default(0);
            $table->decimal('weight_used', 8, 2)->nullable();
            $table->integer('calories_burned')->nullable();
            $table->integer('rest_time_used')->nullable();
            $table->enum('difficulty_felt', ['too_easy', 'easy', 'just_right', 'hard', 'too_hard'])->nullable();
            $table->integer('effort_level')->nullable()->comment('Scale 1-10');
            $table->boolean('is_completed')->default(false);
            $table->integer('completion_percentage')->default(0);
            $table->boolean('is_personal_record')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['workout_id', 'order_index']);
            $table->index(['exercise_id']);
            $table->unique(['workout_id', 'exercise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};