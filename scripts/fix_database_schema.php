<?php
/**
 * Database Schema Fix Script
 *
 * This script fixes the missing database columns that cause 500 errors
 * during workout completion. It can be run independently to patch the database.
 *
 * Usage: php scripts/fix_database_schema.php
 */

require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "🔧 Fixing Database Schema Issues...\n\n";

try {
    // Check if we can connect to the database
    DB::connection()->getPdo();
    echo "✅ Database connection successful\n";

    $issuesFixed = 0;

    // Fix 1: Add missing actual_duration column
    if (!Schema::hasColumn('workouts', 'actual_duration')) {
        echo "❌ Missing: actual_duration column in workouts table\n";
        Schema::table('workouts', function (Blueprint $table) {
            $table->integer('actual_duration')->nullable();
        });
        echo "✅ Fixed: Added actual_duration column\n";
        $issuesFixed++;
    } else {
        echo "✅ OK: actual_duration column exists\n";
    }

    // Fix 2: Add missing actual_calories column
    if (!Schema::hasColumn('workouts', 'actual_calories')) {
        echo "❌ Missing: actual_calories column in workouts table\n";
        Schema::table('workouts', function (Blueprint $table) {
            $table->integer('actual_calories')->nullable();
        });
        echo "✅ Fixed: Added actual_calories column\n";
        $issuesFixed++;
    } else {
        echo "✅ OK: actual_calories column exists\n";
    }

    // Fix 3: Add missing is_public column
    if (!Schema::hasColumn('workouts', 'is_public')) {
        echo "❌ Missing: is_public column in workouts table\n";
        Schema::table('workouts', function (Blueprint $table) {
            $table->boolean('is_public')->default(false);
        });
        echo "✅ Fixed: Added is_public column\n";
        $issuesFixed++;
    } else {
        echo "✅ OK: is_public column exists\n";
    }

    // Fix 4: Update existing completed workouts with reasonable defaults
    echo "\n🔄 Updating existing workout data...\n";

    $updatedDuration = DB::table('workouts')
        ->whereNull('actual_duration')
        ->where('status', 'completed')
        ->update([
            'actual_duration' => DB::raw('COALESCE(estimated_duration, 30)')
        ]);

    $updatedCalories = DB::table('workouts')
        ->whereNull('actual_calories')
        ->where('status', 'completed')
        ->update([
            'actual_calories' => DB::raw('COALESCE(estimated_calories, 150)')
        ]);

    echo "✅ Updated {$updatedDuration} workouts with default duration\n";
    echo "✅ Updated {$updatedCalories} workouts with default calories\n";

    // Fix 5: Check goals table if it exists
    if (Schema::hasTable('goals')) {
        if (!Schema::hasColumn('goals', 'active')) {
            echo "❌ Missing: active column in goals table\n";
            Schema::table('goals', function (Blueprint $table) {
                $table->boolean('active')->default(true);
            });
            echo "✅ Fixed: Added active column to goals table\n";
            $issuesFixed++;
        } else {
            echo "✅ OK: active column exists in goals table\n";
        }
    } else {
        echo "ℹ️  Goals table doesn't exist - this is OK\n";
    }

    // Final verification
    echo "\n🔍 Final Verification:\n";

    $workoutColumns = Schema::getColumnListing('workouts');
    $requiredColumns = ['actual_duration', 'actual_calories', 'is_public'];

    foreach ($requiredColumns as $column) {
        if (in_array($column, $workoutColumns)) {
            echo "✅ {$column} column verified\n";
        } else {
            echo "❌ {$column} column still missing!\n";
        }
    }

    // Test a simple query to make sure it works
    echo "\n🧪 Testing database queries...\n";

    try {
        $testQuery = DB::table('workouts')
            ->select('id', 'name', 'actual_duration', 'actual_calories', 'status')
            ->limit(1)
            ->first();

        echo "✅ Test query successful\n";

        if ($testQuery) {
            echo "   Sample workout: {$testQuery->name} (Duration: {$testQuery->actual_duration}min)\n";
        }
    } catch (\Exception $e) {
        echo "❌ Test query failed: " . $e->getMessage() . "\n";
    }

    // Summary
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 Database Schema Fix Complete!\n";
    echo "Fixed {$issuesFixed} schema issues\n";

    if ($issuesFixed > 0) {
        echo "\n💡 Recommended next steps:\n";
        echo "1. Test workout completion at: POST /api/workouts/logs/[ID]/complete\n";
        echo "2. Clear application cache if needed\n";
        echo "3. Verify frontend can complete workouts successfully\n";
    } else {
        echo "\n✨ No schema issues found - database should be working correctly!\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Script completed successfully!\n";