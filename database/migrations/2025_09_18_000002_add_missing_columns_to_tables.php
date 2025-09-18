<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing is_public column to workouts table
        if (!Schema::hasColumn('workouts', 'is_public')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->boolean('is_public')->default(false)->after('is_template');
            });
        }

        // Add missing active column to goals table (if goals table exists)
        if (Schema::hasTable('goals') && !Schema::hasColumn('goals', 'active')) {
            Schema::table('goals', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('status');
            });
        }

        // Update existing data - make system templates public
        DB::statement('UPDATE workouts SET is_public = true WHERE user_id = 1 AND is_template = true');

        // Update goals active status based on status if goals table exists
        if (Schema::hasTable('goals')) {
            DB::statement("UPDATE goals SET active = (status = 'active' OR status = 'in_progress')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('workouts', 'is_public')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }

        if (Schema::hasTable('goals') && Schema::hasColumn('goals', 'active')) {
            Schema::table('goals', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};