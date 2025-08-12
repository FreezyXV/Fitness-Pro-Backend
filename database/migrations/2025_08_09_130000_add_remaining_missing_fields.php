<?php
// database/migrations/2025_08_09_130000_add_remaining_missing_fields.php
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
        // Add missing fields to calendar_tasks table ONLY
        Schema::table('calendar_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('calendar_tasks', 'duration')) {
                $table->integer('duration')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('calendar_tasks', 'tags')) {
                $table->json('tags')->nullable()->after('duration');
            }
            if (!Schema::hasColumn('calendar_tasks', 'recurring')) {
                $table->boolean('recurring')->default(false)->after('tags');
            }
            if (!Schema::hasColumn('calendar_tasks', 'recurring_type')) {
                $table->enum('recurring_type', ['daily', 'weekly', 'biweekly', 'monthly'])->nullable()->after('recurring');
            }
            if (!Schema::hasColumn('calendar_tasks', 'recurring_end_date')) {
                $table->date('recurring_end_date')->nullable()->after('recurring_type');
            }
        });

        // Add priority to goals table ONLY (if missing)
        Schema::table('goals', function (Blueprint $table) {
            if (!Schema::hasColumn('goals', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('category');
            }
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_tasks', function (Blueprint $table) {
            $table->dropColumn(['duration', 'tags', 'recurring', 'recurring_type', 'recurring_end_date']);
        });

        Schema::table('goals', function (Blueprint $table) {
            if (Schema::hasColumn('goals', 'priority')) {
                $table->dropColumn('priority');
            }
        });

        
    }
};