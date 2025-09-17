<?php
// app/Services/StreakCalculatorService.php
namespace App\Services;

use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StreakCalculatorService
{
    /**
     * Calculate current workout streak for a user
     *
     * @param User $user
     * @return int
     */
    public function calculateWorkoutStreak(User $user): int
    {
        $sessions = $user->workouts()
                        ->where('is_template', false)
                        ->where('status', 'completed')
                        ->orderBy('completed_at', 'desc')
                        ->pluck('completed_at')
                        ->map(function ($date) {
                            return Carbon::parse($date)->format('Y-m-d');
                        })
                        ->unique()
                        ->values();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = Carbon::today();

        // Check if user worked out today or yesterday to start the streak
        $lastWorkoutDate = Carbon::parse($sessions->first());
        $daysSinceLastWorkout = $currentDate->diffInDays($lastWorkoutDate);

        if ($daysSinceLastWorkout > 1) {
            return 0; // Streak is broken if more than 1 day since last workout
        }

        // Start counting from today or yesterday
        $checkDate = $daysSinceLastWorkout === 0 ? $currentDate : $currentDate->copy()->subDay();

        foreach ($sessions as $sessionDate) {
            $sessionCarbon = Carbon::parse($sessionDate);
            
            if ($sessionCarbon->isSameDay($checkDate)) {
                $streak++;
                $checkDate = $checkDate->subDay();
            } else {
                // Check if we skipped a day (break in streak)
                $daysDiff = $checkDate->diffInDays($sessionCarbon);
                if ($daysDiff > 0) {
                    break; // Streak is broken
                }
            }
        }

        return $streak;
    }

    /**
     * Calculate longest streak for a user
     *
     * @param User $user
     * @return int
     */
    public function calculateLongestStreak(User $user): int
    {
        $sessions = $user->workouts()
                        ->where('is_template', false)
                        ->where('status', 'completed')
                        ->orderBy('completed_at', 'asc')
                        ->pluck('completed_at')
                        ->map(function ($date) {
                            return Carbon::parse($date)->format('Y-m-d');
                        })
                        ->unique()
                        ->values();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $longestStreak = 0;
        $currentStreak = 1;
        $previousDate = Carbon::parse($sessions->first());

        for ($i = 1; $i < $sessions->count(); $i++) {
            $currentDate = Carbon::parse($sessions[$i]);
            $daysDiff = $previousDate->diffInDays($currentDate);

            if ($daysDiff === 1) {
                // Consecutive day
                $currentStreak++;
            } else {
                // Streak broken, check if it's the longest
                $longestStreak = max($longestStreak, $currentStreak);
                $currentStreak = 1; // Reset streak
            }

            $previousDate = $currentDate;
        }

        // Check final streak
        return max($longestStreak, $currentStreak);
    }

    /**
     * Calculate consistency stats for a user
     *
     * @param User $user
     * @return array
     */
    public function calculateConsistencyStats(User $user): array
    {
        $sessions = $user->workouts()
                        ->where('is_template', false)
                        ->where('status', 'completed')
                        ->where('completed_at', '>=', Carbon::now()->subDays(30))
                        ->get();

        $currentStreak = $this->calculateWorkoutStreak($user);
        $longestStreak = $this->calculateLongestStreak($user);
        
        // Calculate weekly consistency (last 4 weeks)
        $weeklyConsistency = $this->calculateWeeklyConsistency($user);
        
        // Calculate monthly consistency (last 12 months)
        $monthlyConsistency = $this->calculateMonthlyConsistency($user);

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'workouts_this_month' => $sessions->count(),
            'weekly_consistency' => $weeklyConsistency,
            'monthly_consistency' => $monthlyConsistency,
            'streak_level' => $this->getStreakLevel($currentStreak),
            'consistency_percentage' => $this->calculateConsistencyPercentage($user, 30)
        ];
    }

    /**
     * Calculate weekly consistency for the last 4 weeks
     *
     * @param User $user
     * @return array
     */
    private function calculateWeeklyConsistency(User $user): array
    {
        $weeks = [];
        
        for ($i = 0; $i < 4; $i++) {
            $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
            $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $workoutsThisWeek = $user->workouts()
                                   ->where('is_template', false)
                                   ->where('status', 'completed')
                                   ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
                                   ->count();
            
            $weeks[] = [
                'week_start' => $startOfWeek->format('Y-m-d'),
                'week_end' => $endOfWeek->format('Y-m-d'),
                'workouts' => $workoutsThisWeek,
                'goal_met' => $workoutsThisWeek >= 3 // Assuming goal of 3 workouts per week
            ];
        }
        
        return array_reverse($weeks); // Most recent week first
    }

    /**
     * Calculate monthly consistency for the last 12 months
     *
     * @param User $user
     * @return array
     */
    private function calculateMonthlyConsistency(User $user): array
    {
        $months = [];
        
        for ($i = 0; $i < 12; $i++) {
            $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfMonth = Carbon::now()->subMonths($i)->endOfMonth();
            
            $workoutsThisMonth = $user->workouts()
                                    ->where('is_template', false)
                                    ->where('status', 'completed')
                                    ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                                    ->count();
            
            $months[] = [
                'month' => $startOfMonth->format('Y-m'),
                'month_name' => $startOfMonth->format('F Y'),
                'workouts' => $workoutsThisMonth,
                'goal_met' => $workoutsThisMonth >= 12 // Assuming goal of 12 workouts per month
            ];
        }
        
        return array_reverse($months); // Most recent month first
    }

    /**
     * Calculate consistency percentage for a given period
     *
     * @param User $user
     * @param int $days
     * @return float
     */
    public function calculateConsistencyPercentage(User $user, int $days = 30): float
    {
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();
        
        $workoutDays = $user->workouts()
                          ->where('is_template', false)
                          ->where('status', 'completed')
                          ->whereBetween('completed_at', [$startDate, $endDate])
                          ->selectRaw('DATE(completed_at) as workout_date')
                          ->distinct()
                          ->count();
        
        return round(($workoutDays / $days) * 100, 1);
    }

    /**
     * Predict streak maintenance probability
     *
     * @param User $user
     * @return array
     */
    public function predictStreakMaintenance(User $user): array
    {
        $consistencyPercentage = $this->calculateConsistencyPercentage($user, 30);
        $currentStreak = $this->calculateWorkoutStreak($user);
        
        // Simple prediction based on consistency
        $probability = min(100, $consistencyPercentage * 1.2); // Boost for current performance
        
        // Adjust based on streak length (longer streaks are more likely to continue)
        if ($currentStreak >= 7) {
            $probability += 10;
        }
        if ($currentStreak >= 14) {
            $probability += 5;
        }
        if ($currentStreak >= 30) {
            $probability += 5;
        }
        
        $probability = min(100, max(0, $probability));
        
        $riskLevel = 'low';
        if ($probability < 50) {
            $riskLevel = 'high';
        } elseif ($probability < 75) {
            $riskLevel = 'medium';
        }
        
        return [
            'probability' => round($probability),
            'risk_level' => $riskLevel,
            'recommendation' => $this->getStreakRecommendation($probability, $currentStreak)
        ];
    }

    /**
     * Get streak level based on current streak
     *
     * @param int $streak
     * @return string
     */
    private function getStreakLevel(int $streak): string
    {
        if ($streak >= 365) return 'legendary';
        if ($streak >= 100) return 'epic';
        if ($streak >= 30) return 'gold';
        if ($streak >= 14) return 'silver';
        if ($streak >= 7) return 'bronze';
        if ($streak >= 3) return 'copper';
        
        return 'beginner';
    }

    /**
     * Get recommendation based on streak maintenance probability
     *
     * @param float $probability
     * @param int $currentStreak
     * @return string
     */
    private function getStreakRecommendation(float $probability, int $currentStreak): string
    {
        if ($probability >= 80) {
            return "Excellent consistency! Keep up the great work.";
        } elseif ($probability >= 60) {
            return "Good momentum! Try to establish a regular schedule.";
        } elseif ($probability >= 40) {
            return "You're building habits! Consider setting workout reminders.";
        } else {
            return "Focus on consistency over intensity. Start with shorter, more frequent workouts.";
        }
    }

    /**
     * Get streak milestones and progress
     *
     * @param User $user
     * @return array
     */
    public function getStreakMilestones(User $user): array
    {
        $currentStreak = $this->calculateWorkoutStreak($user);
        $milestones = [3, 7, 14, 30, 60, 100, 365];
        
        $nextMilestone = null;
        $progress = 0;
        
        foreach ($milestones as $milestone) {
            if ($currentStreak < $milestone) {
                $nextMilestone = $milestone;
                $previousMilestone = $milestones[array_search($milestone, $milestones) - 1] ?? 0;
                $progress = (($currentStreak - $previousMilestone) / ($milestone - $previousMilestone)) * 100;
                break;
            }
        }
        
        return [
            'current_streak' => $currentStreak,
            'next_milestone' => $nextMilestone,
            'progress_to_next' => round($progress, 1),
            'achieved_milestones' => array_filter($milestones, fn($m) => $currentStreak >= $m)
        ];
    }

    /**
     * Obtenir les prochains milestones à atteindre
     */
    public function getUpcomingMilestones(User $user): array
    {
        $totalSessions = $user->workouts()->where('is_template', false)->where('status', 'completed')->count();
        $currentStreak = $this->calculateWorkoutStreak($user);
        $totalCalories = $user->workouts()->where('is_template', false)->where('status', 'completed')->sum('actual_calories');
        $totalHours = $user->workouts()->where('is_template', false)->where('status', 'completed')->sum('actual_duration') / 60;

        $upcoming = [];

        // Prochain milestone de sessions
        $sessionMilestones = [1, 5, 10, 25, 50, 100, 250, 500, 1000];
        foreach ($sessionMilestones as $milestone) {
            if ($milestone > $totalSessions) {
                $upcoming[] = [
                    'type' => 'sessions',
                    'target' => $milestone,
                    'current' => $totalSessions,
                    'remaining' => $milestone - $totalSessions,
                    'progress' => ($totalSessions / $milestone) * 100
                ];
                break;
            }
        }

        // Prochain milestone de streak
        $streakMilestones = [3, 7, 14, 30, 60, 100, 365];
        foreach ($streakMilestones as $milestone) {
            if ($milestone > $currentStreak) {
                $upcoming[] = [
                    'type' => 'streak',
                    'target' => $milestone,
                    'current' => $currentStreak,
                    'remaining' => $milestone - $currentStreak,
                    'progress' => ($currentStreak / $milestone) * 100
                ];
                break;
            }
        }

        return $upcoming;
    }

    /**
     * Check if user has achieved any milestones (optimized)
     */
    public function checkMilestones(User $user): array
    {
        // Get all stats in one query
        $stats = $user->workouts()
            ->where('is_template', false)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_sessions, SUM(actual_calories) as total_calories')
            ->first();
        
        $currentStreak = $this->calculateWorkoutStreak($user);
        
        $milestones = [];
        
        // Session milestones
        $sessionMilestones = [1, 5, 10, 25, 50, 100, 250, 500];
        foreach ($sessionMilestones as $milestone) {
            if ($stats->total_sessions >= $milestone) {
                $milestones[] = [
                    'type' => 'sessions',
                    'value' => $milestone,
                    'achieved' => true,
                    'title' => "{$milestone} séances complétées"
                ];
            }
        }
        
        // Streak milestones
        $streakMilestones = [3, 7, 14, 30, 60, 100];
        foreach ($streakMilestones as $milestone) {
            if ($currentStreak >= $milestone) {
                $milestones[] = [
                    'type' => 'streak',
                    'value' => $milestone,
                    'achieved' => true,
                    'title' => "{$milestone} jours consécutifs"
                ];
            }
        }
        
        // Calorie milestones
        $calorieMilestones = [1000, 5000, 10000, 25000, 50000];
        foreach ($calorieMilestones as $milestone) {
            if ($stats->total_calories >= $milestone) {
                $milestones[] = [
                    'type' => 'calories',
                    'value' => $milestone,
                    'achieved' => true,
                    'title' => "{$milestone} calories brûlées"
                ];
            }
        }
        
        return $milestones;
    }

    /**
     * Get the next overall milestone for a user
     *
     * @param User $user
     * @return array|null
     */
    public function getOverallNextMilestone(User $user): ?array
    {
        $totalSessions = $user->workouts()->where('is_template', false)->where('status', 'completed')->count();
        $currentStreak = $this->calculateWorkoutStreak($user);
        $totalCalories = $user->workouts()->where('is_template', false)->where('status', 'completed')->sum('actual_calories');

        $nextSessionMilestone = null;
        $sessionMilestones = [1, 5, 10, 25, 50, 100, 250, 500, 1000];
        foreach ($sessionMilestones as $milestone) {
            if ($totalSessions < $milestone) {
                $nextSessionMilestone = [
                    'type' => 'sessions',
                    'target' => $milestone,
                    'current' => $totalSessions,
                    'remaining' => $milestone - $totalSessions,
                    'progress' => round(($totalSessions / $milestone) * 100, 1)
                ];
                break;
            }
        }

        $nextStreakMilestone = null;
        $streakMilestones = [3, 7, 14, 30, 60, 100, 365];
        foreach ($streakMilestones as $milestone) {
            if ($currentStreak < $milestone) {
                $nextStreakMilestone = [
                    'type' => 'streak',
                    'target' => $milestone,
                    'current' => $currentStreak,
                    'remaining' => $milestone - $currentStreak,
                    'progress' => round(($currentStreak / $milestone) * 100, 1)
                ];
                break;
            }
        }

        $nextCalorieMilestone = null;
        $calorieMilestones = [1000, 5000, 10000, 25000, 50000];
        foreach ($calorieMilestones as $milestone) {
            if ($totalCalories < $milestone) {
                $nextCalorieMilestone = [
                    'type' => 'calories',
                    'target' => $milestone,
                    'current' => $totalCalories,
                    'remaining' => $milestone - $totalCalories,
                    'progress' => round(($totalCalories / $milestone) * 100, 1)
                ];
                break;
            }
        }

        $allMilestones = array_filter([
            $nextSessionMilestone,
            $nextStreakMilestone,
            $nextCalorieMilestone
        ]);

        // Sort by remaining value to get the closest milestone
        usort($allMilestones, fn($a, $b) => $a['remaining'] <=> $b['remaining']);

        return $allMilestones[0] ?? null;
    }
}