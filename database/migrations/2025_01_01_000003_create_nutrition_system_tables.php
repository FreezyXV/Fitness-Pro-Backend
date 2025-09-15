<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates nutrition tracking and management system:
     * - aliments: Food database with nutritional information
     * - nutrition_goals: User nutritional targets
     * - meal_entries: Daily food logging
     * - water_intakes: Hydration tracking
     * - user_diets: Diet regime management
     */
    public function up(): void
    {
        // === FOOD DATABASE ===
        Schema::create('aliments', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('category')->nullable(); // fruits, vegetables, proteins, grains, etc.

            // Nutritional Information (per 100g/100ml)
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('proteins', 8, 2)->default(0); // grams
            $table->decimal('carbohydrates', 8, 2)->default(0); // grams
            $table->decimal('fats', 8, 2)->default(0); // grams
            $table->decimal('fiber', 8, 2)->default(0); // grams
            $table->decimal('sugar', 8, 2)->default(0); // grams
            $table->decimal('sodium', 8, 2)->default(0); // mg
            $table->decimal('potassium', 8, 2)->default(0); // mg
            $table->decimal('vitamin_c', 8, 2)->default(0); // mg

            // Additional Data
            $table->string('unit')->default('g'); // g, ml, piece, cup, etc.
            $table->decimal('serving_size', 8, 2)->default(100); // Default serving size
            $table->string('barcode')->nullable();
            $table->string('image_url')->nullable();

            // System
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['category', 'is_active']);
            $table->index(['name', 'brand']);
            $table->index('barcode');
        });

        // === NUTRITION GOALS ===
        Schema::create('nutrition_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Macronutrients (daily targets)
            $table->decimal('target_calories', 8, 2);
            $table->decimal('target_proteins', 8, 2); // grams
            $table->decimal('target_carbohydrates', 8, 2); // grams
            $table->decimal('target_fats', 8, 2); // grams

            // Micronutrients (daily targets)
            $table->decimal('target_fiber', 8, 2)->default(25); // grams
            $table->decimal('target_sodium', 8, 2)->default(2300); // mg
            $table->decimal('target_potassium', 8, 2)->default(3500); // mg
            $table->decimal('target_vitamin_c', 8, 2)->default(90); // mg

            // Hydration
            $table->decimal('target_water', 8, 2)->default(2000); // ml

            // System
            $table->timestamps();

            // One set of goals per user
            $table->unique('user_id');
        });

        // === MEAL LOGGING ===
        Schema::create('meal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('aliment_id')->constrained()->onDelete('cascade');

            // Meal Information
            $table->date('date');
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack'])->default('lunch');
            $table->decimal('quantity', 8, 2); // Amount consumed
            $table->string('unit')->default('g'); // g, ml, piece, cup, etc.

            // Calculated Nutritional Values (stored for performance)
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('proteins', 8, 2)->default(0);
            $table->decimal('carbohydrates', 8, 2)->default(0);
            $table->decimal('fats', 8, 2)->default(0);
            $table->decimal('fiber', 8, 2)->default(0);
            $table->decimal('sugar', 8, 2)->default(0);
            $table->decimal('sodium', 8, 2)->default(0);
            $table->decimal('potassium', 8, 2)->default(0);
            $table->decimal('vitamin_c', 8, 2)->default(0);

            // Additional Info
            $table->text('notes')->nullable();
            $table->time('time')->nullable(); // Time of consumption

            // System
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'meal_type', 'date']);
            $table->index(['aliment_id', 'date']);
        });

        // === HYDRATION TRACKING ===
        Schema::create('water_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Intake Information
            $table->date('date');
            $table->decimal('amount', 8, 2); // ml
            $table->time('time'); // Time of intake
            $table->string('source')->default('water'); // water, tea, coffee, juice, etc.

            // System
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'date']);
            $table->index(['date', 'source']);
        });

        // === DIET REGIME MANAGEMENT ===
        Schema::create('user_diets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Diet Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['professional', 'custom', 'ai_generated'])->default('custom');
            $table->enum('category', ['weight_loss', 'muscle_gain', 'maintenance', 'cutting', 'bulking', 'therapeutic'])->nullable();

            // Diet Status
            $table->enum('status', ['active', 'paused', 'completed', 'abandoned'])->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('target_duration_days')->nullable();

            // Progress Tracking
            $table->integer('current_streak')->default(0);
            $table->integer('best_streak')->default(0);
            $table->decimal('compliance_score', 5, 2)->default(0); // 0-100%
            $table->decimal('current_score', 8, 2)->default(0);
            $table->decimal('total_score', 8, 2)->default(0);

            // Diet Configuration (flexible JSON structure)
            $table->json('configuration')->nullable(); // Meal plans, restrictions, preferences
            $table->json('progress_data')->nullable(); // Weight changes, measurements, etc.

            // System
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['type', 'category']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_diets');
        Schema::dropIfExists('water_intakes');
        Schema::dropIfExists('meal_entries');
        Schema::dropIfExists('nutrition_goals');
        Schema::dropIfExists('aliments');
    }
};