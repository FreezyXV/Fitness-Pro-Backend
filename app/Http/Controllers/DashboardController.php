<?php
// app/Http/Controllers/DashboardController.php - SIMPLIFIED VERSION
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkoutSession;
use App\Models\Goal;
use App\Models\WorkoutPlan;

class DashboardController extends BaseController
{
    /**
     * Get dashboard data - simplified without service dependencies
     */
    public function index()
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            
            \Log::info('Dashboard request for user: ' . $user->id);

            // Initialize default data
            $data = [
                'user' => $user->only(['id', 'name', 'email', 'height', 'weight', 'age']),
                'stats' => [
                    'total_workouts' => 0,
                    'total_minutes' => 0,
                    'total_calories' => 0,
                    'current_streak' => 0,
                    'weekly_workouts' => 0,
                    'monthly_workouts' => 0,
                    'active_goals' => 0,
                    'completed_goals' => 0,
                    'has_completed_today' => false,
                    'profile_completion' => 75
                ],
                'recent_workouts' => [],
                'active_goals' => [],
                'current_plan' => null
            ];

            // Try to get real data if tables exist
            try {
                // Check if workout_sessions table exists and get stats
                if (\Schema::hasTable('workout_sessions')) {
                    $workoutStats = WorkoutSession::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->selectRaw('
                            COUNT(*) as total_workouts,
                            COALESCE(SUM(duration_minutes), 0) as total_minutes,
                            COALESCE(SUM(calories_burned), 0) as total_calories
                        ')
                        ->first();

                    if ($workoutStats) {
                        $data['stats']['total_workouts'] = (int) $workoutStats->total_workouts;
                        $data['stats']['total_minutes'] = (int) $workoutStats->total_minutes;
                        $data['stats']['total_calories'] = (int) $workoutStats->total_calories;
                    }

                    // Get recent workouts
                    $data['recent_workouts'] = WorkoutSession::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->orderBy('completed_at', 'desc')
                        ->limit(5)
                        ->get()
                        ->toArray();

                    // Calculate weekly and monthly
                    $data['stats']['weekly_workouts'] = WorkoutSession::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->whereBetween('completed_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                        ->count();

                    $data['stats']['monthly_workouts'] = WorkoutSession::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->whereBetween('completed_at', [
                            now()->startOfMonth(),
                            now()->endOfMonth()
                        ])
                        ->count();

                    // Check if completed today
                    $data['stats']['has_completed_today'] = WorkoutSession::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->whereDate('completed_at', today())
                        ->exists();
                }

                // Check if goals table exists and get goals
                if (\Schema::hasTable('goals')) {
                    $data['stats']['active_goals'] = Goal::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->count();

                    $data['stats']['completed_goals'] = Goal::where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->count();

                    // Get active goals
                    $activeGoals = Goal::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->orderBy('target_date', 'asc')
                        ->limit(3)
                        ->get();

                    $data['active_goals'] = $activeGoals->map(function($goal) {
                        $goalArray = $goal->toArray();
                        return $goalArray;
                    })->toArray();
                }

                // Check if workout_plans table exists and get current plan
                if (\Schema::hasTable('workout_plans')) {
                    $currentPlan = WorkoutPlan::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->first();

                    if ($currentPlan) {
                        $data['current_plan'] = $currentPlan->toArray();
                    }
                }

                // Simple streak calculation
                if (\Schema::hasTable('workout_sessions')) {
                    $data['stats']['current_streak'] = $this->calculateSimpleStreak($user->id);
                }

            } catch (\Exception $e) {
                \Log::warning('Dashboard data calculation failed: ' . $e->getMessage());
                // Keep default data if calculation fails
            }

            \Log::info('Dashboard data prepared successfully');

            return $this->successResponse($data, 'Dashboard data loaded successfully');

        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return $this->errorResponse('Dashboard loading failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Simple streak calculation without complex dependencies
     */
    private function calculateSimpleStreak(int $userId): int
    {
        try {
            $workoutDates = WorkoutSession::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->selectRaw('DATE(completed_at) as workout_date')
                ->distinct()
                ->orderBy('workout_date', 'desc')
                ->limit(30)
                ->pluck('workout_date')
                ->toArray();

            if (empty($workoutDates)) {
                return 0;
            }

            $streak = 0;
            $currentDate = now()->startOfDay();
            
            // Check if workout was done today or yesterday
            $lastWorkoutDate = \Carbon\Carbon::parse($workoutDates[0]);
            $daysSinceLastWorkout = $currentDate->diffInDays($lastWorkoutDate);
            
            if ($daysSinceLastWorkout > 1) {
                return 0; // Streak is broken
            }
            
            // Start counting from the appropriate date
            $checkDate = $daysSinceLastWorkout === 0 ? $currentDate : $currentDate->copy()->subDay();
            
            foreach ($workoutDates as $workoutDate) {
                $workoutCarbon = \Carbon\Carbon::parse($workoutDate);
                
                if ($workoutCarbon->isSameDay($checkDate)) {
                    $streak++;
                    $checkDate = $checkDate->subDay();
                } else {
                    break; // Streak is broken
                }
            }

            return $streak;

        } catch (\Exception $e) {
            \Log::warning('Streak calculation error: ' . $e->getMessage());
            return 0;
        }
    }
}