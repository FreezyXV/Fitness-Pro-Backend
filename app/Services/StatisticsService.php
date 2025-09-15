<?php
// app/Services/StatisticsService.php - OPTIMIZED AND MERGED VERSION
namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use App\Models\UserExerciseProgress;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StatisticsService
{
    protected StreakCalculatorService $streakCalculator;

    public function __construct(StreakCalculatorService $streakCalculator)
    {
        $this->streakCalculator = $streakCalculator;
    }

    /**
     * Get comprehensive user workout statistics with caching
     */
    public function getUserStats(User $user): array
    {
        return Cache::remember("user_complete_stats_{$user->id}", 900, function () use ($user) {
            $sessions = $user->workouts()->where('is_template', false)->where('status', 'completed');
            $consistencyStats = $this->streakCalculator->calculateConsistencyStats($user);
            
            $baseStats = [
                'total_sessions' => $sessions->count(),
                'total_minutes' => $sessions->sum('duration_minutes') ?? 0,
                'total_calories' => $sessions->sum('calories_burned') ?? 0,
                'average_duration' => round($sessions->avg('duration_minutes') ?? 0, 1),
                'average_calories' => round($sessions->avg('calories_burned') ?? 0, 1),
                'this_week' => $this->getWeeklyCount($user),
                'this_month' => $this->getMonthlyCount($user),
                'longest_session' => $sessions->max('duration_minutes') ?? 0,
                'most_calories' => $sessions->max('calories_burned') ?? 0,
            ];

            return array_merge($baseStats, ['consistency' => $consistencyStats]);
        });
    }

    /**
     * Get weekly workout data for charts (optimized)
     */
    public function getWeeklyData(User $user): array
    {
        return Cache::remember("user_weekly_data_{$user->id}", 600, function () use ($user) {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            
            // Single query to get all sessions for the week
            $sessions = $user->workouts()
                ->where('is_template', false)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->selectRaw('DATE(completed_at) as date, COUNT(*) as sessions, SUM(duration_minutes) as minutes, SUM(calories_burned) as calories')
                ->groupBy('date')
                ->get()
                ->keyBy('date');
            
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dateString = $date->format('Y-m-d');
                $sessionData = $sessions->get($dateString);
                
                $data[] = [
                    'date' => $dateString,
                    'day' => $date->format('l'),
                    'sessions' => $sessionData->sessions ?? 0,
                    'minutes' => $sessionData->minutes ?? 0,
                    'calories' => $sessionData->calories ?? 0
                ];
            }
            
            return $data;
        });
    }

    /**
     * Get monthly overview with comparison (optimized)
     */
    public function getMonthlyOverview(User $user): array
    {
        return Cache::remember("user_monthly_overview_{$user->id}", 1800, function () use ($user) {
            $currentMonth = Carbon::now();
            $previousMonth = Carbon::now()->subMonth();
            
            $currentData = $this->getMonthData($user, $currentMonth);
            $previousData = $this->getMonthData($user, $previousMonth);
            
            return [
                'current_month' => array_merge($currentData, [
                    'name' => $currentMonth->format('F Y')
                ]),
                'previous_month' => array_merge($previousData, [
                    'name' => $previousMonth->format('F Y')
                ]),
                'comparison' => [
                    'sessions_change' => $this->calculatePercentageChange(
                        $previousData['sessions'], 
                        $currentData['sessions']
                    ),
                    'minutes_change' => $this->calculatePercentageChange(
                        $previousData['minutes'], 
                        $currentData['minutes']
                    ),
                    'calories_change' => $this->calculatePercentageChange(
                        $previousData['calories'], 
                        $currentData['calories']
                    ),
                ]
            ];
        });
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends(User $user, int $months = 6): array
    {
        return Cache::remember("user_trends_{$user->id}_{$months}", 3600, function () use ($user, $months) {
            $trends = [];
            
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthData = $this->getMonthData($user, $month);
                
                $trends[] = array_merge($monthData, [
                    'month' => $month->format('Y-m'),
                    'month_name' => $month->format('F Y'),
                    'avg_session_duration' => $monthData['sessions'] > 0 
                        ? round($monthData['minutes'] / $monthData['sessions'], 1) 
                        : 0,
                    'avg_calories_per_session' => $monthData['sessions'] > 0 
                        ? round($monthData['calories'] / $monthData['sessions'], 1) 
                        : 0
                ]);
            }
            
            return $trends;
        });
    }

    /**
     * Get user ranking compared to other users (simplified)
     */
    public function getUserRanking(User $user): array
    {
        return Cache::remember("user_ranking_{$user->id}", 1800, function () use ($user) {
            $userStats = $this->getUserStats($user);
            
            // Get rankings for different metrics
            $totalSessionsRank = User::whereHas('workouts', function ($q) use ($userStats) {
                $q->where('status', 'completed')
                  ->where('is_template', false)
                  ->havingRaw('COUNT(*) > ?', [$userStats['total_sessions']]);
            })->count() + 1;
            
            $totalCaloriesRank = User::whereHas('workouts', function ($q) use ($userStats) {
                $q->where('status', 'completed')
                  ->where('is_template', false)
                  ->havingRaw('SUM(calories_burned) > ?', [$userStats['total_calories']]);
            })->count() + 1;
            
            $totalUsers = User::whereHas('workouts', function ($q) {
                $q->where('is_template', false);
            })->count();
            
            return [
                'total_sessions_rank' => $totalSessionsRank,
                'total_calories_rank' => $totalCaloriesRank,
                'total_users' => $totalUsers,
                'percentile' => [
                    'sessions' => round((($totalUsers - $totalSessionsRank + 1) / $totalUsers) * 100, 1),
                    'calories' => round((($totalUsers - $totalCaloriesRank + 1) / $totalUsers) * 100, 1),
                ]
            ];
        });
    }

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(User $user): array
    {
        return Cache::remember("user_dashboard_stats_{$user->id}", 600, function () use ($user) {
            $baseStats = $this->getUserStats($user);
            $weeklyData = $this->getWeeklyData($user);
            $consistency = $this->streakCalculator->calculateConsistencyPercentage($user);
            $milestones = $this->streakCalculator->checkMilestones($user);
            
            // Add physical statistics from user profile
            $physicalStats = $this->getPhysicalStats($user);
            
            return [
                'overview' => $baseStats,
                'weekly_data' => $weeklyData,
                'consistency_score' => $consistency,
                'recent_milestones' => array_slice($milestones, -3), // Last 3 milestones
                'next_milestone' => $this->streakCalculator->getOverallNextMilestone($user),
                'performance_trend' => $this->getPerformanceTrend($user),
                'physical_stats' => $physicalStats, // Added physical statistics
            ];
        });
    }

    /**
     * Get physical statistics from user profile
     */
    public function getPhysicalStats(User $user): array
    {
        return Cache::remember("user_physical_stats_{$user->id}", 900, function () use ($user) {
            // Get basic profile information with proper null handling
            $physicalStats = [
                'age' => $user->age ?: null,
                'height' => $user->height ?: null,
                'weight' => $user->weight ?: null,
                'gender' => $user->gender ?: null,
                'activity_level' => $user->activity_level ?: 'moderately_active',
                'blood_group' => $user->blood_group ?: null,
                'has_complete_profile' => $this->hasCompletePhysicalProfile($user),
            ];
            
            // Get BMI information
            $bmiInfo = $user->getBmiInfo();
            $physicalStats['bmi'] = $bmiInfo;
            
            // Get profile completion
            $physicalStats['profile_completion'] = $user->getProfileCompletionPercentage();
            
            // Calculate additional metrics only if we have complete data
            if ($this->hasCompletePhysicalProfile($user)) {
                $physicalStats['bmr'] = $this->calculateBMR($user);
                $physicalStats['daily_calories'] = $this->calculateDailyCalories($user);
                $physicalStats['ideal_weight_range'] = $this->calculateIdealWeightRange($user);
            } else {
                // Provide default values when data is incomplete
                $physicalStats['bmr'] = null;
                $physicalStats['daily_calories'] = null;
                $physicalStats['ideal_weight_range'] = [
                    'min' => null,
                    'max' => null,
                    'current' => $user->weight,
                    'status' => 'unknown'
                ];
                $physicalStats['missing_data_message'] = 'Veuillez compléter votre profil (âge, taille, poids, genre) pour obtenir des statistiques personnalisées.';
            }
            
            return $physicalStats;
        });
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    private function getWeeklyCount(User $user): int
    {
        return $user->workouts()
            ->where('is_template', false)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();
    }

    private function getMonthlyCount(User $user): int
    {
        return $user->workouts()
            ->where('is_template', false)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->count();
    }

    private function getMonthData(User $user, Carbon $month): array
    {
        $sessions = $user->workouts()
            ->where('is_template', false)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()
            ])
            ->selectRaw('COUNT(*) as sessions, SUM(duration_minutes) as minutes, SUM(calories_burned) as calories')
            ->first();
        
        return [
            'sessions' => $sessions->sessions ?? 0,
            'minutes' => $sessions->minutes ?? 0,
            'calories' => $sessions->calories ?? 0,
        ];
    }

    private function calculatePercentageChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getNextMilestone(User $user): ?array
    {
        $totalSessions = $user->workouts()->where('is_template', false)->where('status', 'completed')->count();
        $milestones = [1, 5, 10, 25, 50, 100, 250, 500, 1000];
        
        foreach ($milestones as $milestone) {
            if ($totalSessions < $milestone) {
                return [
                    'type' => 'sessions',
                    'target' => $milestone,
                    'current' => $totalSessions,
                    'remaining' => $milestone - $totalSessions,
                    'progress' => round(($totalSessions / $milestone) * 100, 1)
                ];
            }
        }
        
        return null;
    }

    private function getPerformanceTrend(User $user): string
    {
        $thisMonth = $this->getMonthlyCount($user);
        $lastMonth = $user->workouts()
            ->where('is_template', false)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth()
            ])
            ->count();
        
        if ($thisMonth > $lastMonth) {
            return 'improving';
        } elseif ($thisMonth < $lastMonth) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate Basal Metabolic Rate (BMR) using Mifflin-St Jeor Equation
     */
    private function calculateBMR(User $user): int
    {
        if (!$user->weight || !$user->height || !$user->age || !$user->gender) {
            return 0;
        }

        // Mifflin-St Jeor Equation
        // Men: BMR = 10 × weight(kg) + 6.25 × height(cm) - 5 × age(years) + 5
        // Women: BMR = 10 × weight(kg) + 6.25 × height(cm) - 5 × age(years) - 161
        
        $bmr = 10 * $user->weight + 6.25 * $user->height - 5 * $user->age;
        
        if ($user->gender === 'male') {
            $bmr += 5;
        } else {
            $bmr -= 161;
        }
        
        return max(0, round($bmr));
    }

    /**
     * Calculate daily calorie needs based on activity level
     */
    private function calculateDailyCalories(User $user): int
    {
        $bmr = $this->calculateBMR($user);
        
        if ($bmr === 0) {
            return 0;
        }

        // Activity multipliers - mapped to database enum values
        $multipliers = [
            'sedentary' => 1.2,               // Little or no exercise
            'lightly_active' => 1.375,        // Light exercise 1-3 days/week
            'moderately_active' => 1.55,      // Moderate exercise 3-5 days/week
            'very_active' => 1.725,           // Heavy exercise 6-7 days/week
            'extremely_active' => 1.9         // Very heavy exercise, physical job
        ];

        $activityLevel = $user->activity_level ?? 'moderately_active';
        $multiplier = $multipliers[$activityLevel] ?? $multipliers['moderately_active'];

        return round($bmr * $multiplier);
    }

    /**
     * Calculate ideal weight range based on height and gender
     */
    private function calculateIdealWeightRange(User $user): array
    {
        if (!$user->height || !$user->gender) {
            return ['min' => null, 'max' => null];
        }

        // Using BMI ranges for ideal weight (18.5 to 24.9)
        $heightInMeters = $user->height / 100;
        $minWeight = round(18.5 * $heightInMeters * $heightInMeters, 1);
        $maxWeight = round(24.9 * $heightInMeters * $heightInMeters, 1);

        return [
            'min' => $minWeight,
            'max' => $maxWeight,
            'current' => $user->weight,
            'status' => $this->getWeightStatus($user->weight, $minWeight, $maxWeight)
        ];
    }

    /**
     * Get weight status based on ideal range
     */
    private function getWeightStatus(?float $currentWeight, float $minIdeal, float $maxIdeal): string
    {
        if (!$currentWeight) {
            return 'unknown';
        }
        
        if ($currentWeight < $minIdeal) {
            return 'below_ideal';
        } elseif ($currentWeight > $maxIdeal) {
            return 'above_ideal';
        } else {
            return 'ideal';
        }
    }

    /**
     * Check if user has complete physical profile data
     */
    private function hasCompletePhysicalProfile(User $user): bool
    {
        return $user->height && $user->weight && $user->age && $user->gender;
    }

    /**
     * Clear user statistics cache
     */
    public function clearUserCache(User $user): void
    {
        $keys = [
            "user_complete_stats_{$user->id}",
            "user_weekly_data_{$user->id}",
            "user_monthly_overview_{$user->id}",
            "user_milestones_{$user->id}",
            "user_consistency_{$user->id}_30",
            "user_trends_{$user->id}_6",
            "user_ranking_{$user->id}",
            "user_dashboard_stats_{$user->id}",
            "user_physical_stats_{$user->id}", // Added physical stats cache
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}