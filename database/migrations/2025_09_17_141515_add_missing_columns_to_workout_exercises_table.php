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
        Schema::table('workout_exercises', function (Blueprint $table) {
            // Add columns expected by Workout model's withPivot
            if (!Schema::hasColumn('workout_exercises', 'sets')) {
                $table->integer('sets')->nullable()->after('order_index');
            }
            if (!Schema::hasColumn('workout_exercises', 'reps')) {
                $table->integer('reps')->nullable()->after('sets');
            }
            if (!Schema::hasColumn('workout_exercises', 'duration_seconds')) {
                $table->integer('duration_seconds')->nullable()->after('reps');
            }
            if (!Schema::hasColumn('workout_exercises', 'rest_time_seconds')) {
                $table->integer('rest_time_seconds')->nullable()->after('duration_seconds');
            }
            if (!Schema::hasColumn('workout_exercises', 'target_weight')) {
                $table->decimal('target_weight', 8, 2)->nullable()->after('rest_time_seconds');
            }
            if (!Schema::hasColumn('workout_exercises', 'completed_sets')) {
                $table->integer('completed_sets')->nullable()->after('target_weight');
            }
            if (!Schema::hasColumn('workout_exercises', 'completed_reps')) {
                $table->integer('completed_reps')->nullable()->after('completed_sets');
            }
            if (!Schema::hasColumn('workout_exercises', 'actual_duration_seconds')) {
                $table->integer('actual_duration_seconds')->nullable()->after('completed_reps');
            }
            if (!Schema::hasColumn('workout_exercises', 'weight_used')) {
                $table->decimal('weight_used', 8, 2)->nullable()->after('actual_duration_seconds');
            }
            if (!Schema::hasColumn('workout_exercises', 'is_completed')) {
                $table->boolean('is_completed')->default(false)->after('weight_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $columnsToRemove = [
                'sets', 'reps', 'duration_seconds', 'rest_time_seconds',
                'target_weight', 'completed_sets', 'completed_reps',
                'actual_duration_seconds', 'weight_used', 'is_completed'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('workout_exercises', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
